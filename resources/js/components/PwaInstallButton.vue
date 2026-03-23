<template>
    <Transition name="slide-down">
        <div
            v-if="showButton"
            class="w-full border-b border-blue-200/90 bg-blue-50/95 dark:border-blue-800/80 dark:bg-blue-950/60"
        >
            <!-- iOS instructions -->
            <Transition name="fade">
                <div
                    v-if="showIosInstructions"
                    class="border-b border-blue-200/80 p-3 dark:border-blue-800/60"
                >
                    <p class="mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Install this app on your iPhone
                    </p>
                    <ol class="mb-3 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex gap-2">
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white"
                                >1</span
                            >
                            Tap the <strong>Share</strong> button <Share class="inline-block h-4 w-4" />
                        </li>
                        <li class="flex gap-2">
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white"
                                >2</span
                            >
                            Scroll and tap <strong>Add to Home Screen</strong>
                        </li>
                        <li class="flex gap-2">
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white"
                                >3</span
                            >
                            Tap <strong>Add</strong> to confirm
                        </li>
                    </ol>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            @click="showIosInstructions = false"
                        >
                            Got it
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            @click="dismissPermanently"
                        >
                            Don't show again
                        </button>
                    </div>
                </div>
            </Transition>

            <div class="flex items-center justify-between gap-2 px-2 py-1.5 sm:px-4 sm:py-2">
                <p class="min-w-0 truncate text-xs font-medium text-blue-950 dark:text-blue-100 sm:text-sm">
                    Install the app for quicker access
                </p>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-900 sm:px-3 sm:text-sm"
                        aria-label="Install app"
                        @click="handleInstallClick"
                    >
                        <Download class="h-4 w-4 shrink-0 sm:h-4 sm:w-4" />
                        <span>Install</span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-800/80 hover:bg-blue-100 hover:text-blue-950 dark:text-blue-200/80 dark:hover:bg-blue-900/50 dark:hover:text-blue-50"
                        aria-label="Hide for this session"
                        @click="snoozeForSession"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Download, Share, X } from 'lucide-vue-next';

const STORAGE_KEY = 'pwa-install-dismissed';
const SESSION_SNOOZE_KEY = 'pwa-install-snoozed';

const showButton = ref(false);
const showIosInstructions = ref(false);
let deferredPrompt: any = null;

const isIos = () =>
    /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || (window.navigator as any).standalone === true;
const isDismissed = () => localStorage.getItem(STORAGE_KEY) === '1';
const isSessionSnoozed = () => sessionStorage.getItem(SESSION_SNOOZE_KEY) === '1';

const handleInstallClick = async () => {
    if (isIos()) {
        showIosInstructions.value = true;
        return;
    }

    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            showButton.value = false;
        }
    }
};

const dismissPermanently = () => {
    localStorage.setItem(STORAGE_KEY, '1');
    showIosInstructions.value = false;
    showButton.value = false;
};

const snoozeForSession = () => {
    sessionStorage.setItem(SESSION_SNOOZE_KEY, '1');
    showIosInstructions.value = false;
    showButton.value = false;
};

const captureBeforeInstall = (e: Event) => {
    e.preventDefault();
    deferredPrompt = e;
    if (!isDismissed() && !isSessionSnoozed()) {
        showButton.value = true;
    }
};

const onAppInstalled = () => {
    showButton.value = false;
    deferredPrompt = null;
};

onMounted(() => {
    if (isStandalone() || isDismissed() || isSessionSnoozed()) {
        return;
    }

    if (isIos()) {
        showButton.value = true;
        return;
    }

    window.addEventListener('beforeinstallprompt', captureBeforeInstall);
    window.addEventListener('appinstalled', onAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', captureBeforeInstall);
    window.removeEventListener('appinstalled', onAppInstalled);
});
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition:
        transform 0.2s ease,
        opacity 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    transform: translateY(-0.5rem);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
