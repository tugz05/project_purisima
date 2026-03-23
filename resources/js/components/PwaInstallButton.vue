<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div
        v-if="showButton"
        class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-2"
      >
        <!-- iOS instructions dialog -->
        <Transition name="fade">
          <div
            v-if="showIosInstructions"
            class="mb-2 w-72 rounded-xl bg-white p-4 shadow-lg ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10"
          >
            <p class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">
              Install this app on your iPhone
            </p>
            <ol class="mb-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
              <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white">1</span>
                Tap the <strong>Share</strong> button <Share class="inline-block h-4 w-4" />
              </li>
              <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white">2</span>
                Scroll and tap <strong>Add to Home Screen</strong>
              </li>
              <li class="flex gap-2">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white">3</span>
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

        <!-- Install button -->
        <button
          type="button"
          class="flex items-center gap-2 rounded-full bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-lg transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
          aria-label="Install app"
          @click="handleInstallClick"
        >
          <Download class="h-5 w-5" />
          <span>Install</span>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Download, Share } from 'lucide-vue-next';

const STORAGE_KEY = 'pwa-install-dismissed';

const showButton = ref(false);
const showIosInstructions = ref(false);
let deferredPrompt: any = null;

const isIos = () => /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || (window.navigator as any).standalone === true;
const isDismissed = () => localStorage.getItem(STORAGE_KEY) === '1';

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

const captureBeforeInstall = (e: Event) => {
  e.preventDefault();
  deferredPrompt = e;
  if (!isDismissed()) {
    showButton.value = true;
  }
};

const onAppInstalled = () => {
  showButton.value = false;
  deferredPrompt = null;
};

onMounted(() => {
  if (isStandalone() || isDismissed()) return;

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
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(1rem);
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
