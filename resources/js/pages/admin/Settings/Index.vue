<template>
    <div class="min-h-full bg-gray-100">

        <!-- Hero -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#374151] via-[#1f2937] to-[#111827]
                    md:mx-5 md:mt-5 md:rounded-3xl">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5" />
            <div class="relative px-5 pt-6 pb-6 flex items-end justify-between">
                <div>
                    <p class="text-[13px] font-medium text-gray-400">Admin</p>
                    <h1 class="mt-0.5 text-[22px] font-extrabold leading-tight tracking-tight text-white">
                        System Settings
                    </h1>
                    <p class="mt-1 text-[13px] text-gray-400">Configure your barangay system</p>
                </div>
                <button
                    @click="submitAll"
                    :disabled="saving"
                    class="flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-[13px]
                           font-semibold text-white backdrop-blur-sm hover:bg-white/25 transition-colors disabled:opacity-60"
                >
                    <Save class="h-4 w-4" />
                    {{ saving ? 'Saving…' : 'Save All' }}
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mx-4 mt-4 md:mx-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-[13px] text-emerald-700 font-medium">
            {{ $page.props.flash.success }}
        </div>

        <!-- Setting groups -->
        <div class="px-4 pb-10 pt-4 md:px-5 md:pt-5 space-y-5">
            <div
                v-for="(items, group) in localSettings"
                :key="group"
                class="overflow-hidden rounded-2xl bg-white shadow-sm"
            >
                <!-- Group header -->
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5 bg-gray-50/50">
                    <component :is="groupIcon(group)" class="h-4 w-4 text-gray-400" />
                    <h2 class="text-[13px] font-bold uppercase tracking-widest text-gray-500">{{ groupLabel(group) }}</h2>
                </div>

                <!-- Settings list -->
                <div class="divide-y divide-gray-100">
                    <div v-for="setting in items" :key="setting.key" class="px-5 py-4">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                            <div class="sm:w-56 shrink-0">
                                <p class="text-[13px] font-semibold text-gray-800">{{ setting.label }}</p>
                                <p v-if="setting.description" class="mt-0.5 text-[11px] text-gray-400 leading-relaxed">
                                    {{ setting.description }}
                                </p>
                            </div>
                            <div class="flex-1">
                                <!-- Boolean toggle -->
                                <div v-if="setting.type === 'boolean'" class="flex items-center gap-2">
                                    <button
                                        @click="setting.value = setting.value === '1' ? '0' : '1'"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                        :class="setting.value === '1' ? 'bg-blue-600' : 'bg-gray-200'"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                            :class="setting.value === '1' ? 'translate-x-6' : 'translate-x-1'"
                                        />
                                    </button>
                                    <span class="text-[13px]" :class="setting.value === '1' ? 'text-blue-600 font-medium' : 'text-gray-400'">
                                        {{ setting.value === '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>

                                <!-- Textarea -->
                                <textarea
                                    v-else-if="setting.type === 'textarea'"
                                    v-model="setting.value"
                                    rows="3"
                                    class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-gray-900
                                           focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300 resize-none"
                                />

                                <!-- Text input -->
                                <input
                                    v-else
                                    v-model="setting.value"
                                    type="text"
                                    class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-gray-900
                                           focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import { Save, Building2, Phone, Zap, FileText, Info } from 'lucide-vue-next';

defineOptions({ name: 'AdminSettingsIndex', layout: AdminLayout });

interface SettingItem {
    id: number;
    key: string;
    label: string;
    value: string | null;
    type: string;
    group: string;
    description: string | null;
}

const props = defineProps<{
    settings: Record<string, SettingItem[]>;
}>();

// Deep-copy for local editing
const localSettings = reactive<Record<string, SettingItem[]>>(
    Object.fromEntries(
        Object.entries(props.settings).map(([g, items]) => [
            g,
            items.map(s => ({ ...s, value: s.value ?? '' })),
        ])
    )
);

const saving = ref(false);

function submitAll() {
    const allSettings = Object.values(localSettings).flat().map(s => ({
        key:   s.key,
        value: s.value,
    }));

    saving.value = true;
    router.post(admin.settings.update().url, { settings: allSettings }, {
        onFinish: () => { saving.value = false; },
    });
}

const GROUP_ICONS: Record<string, any> = {
    general:     Building2,
    contact:     Phone,
    features:    Zap,
    certificate: FileText,
};

const GROUP_LABELS: Record<string, string> = {
    general:     'General',
    contact:     'Contact Information',
    features:    'Features',
    certificate: 'Certificate',
};

function groupIcon(group: string) {
    return GROUP_ICONS[group] ?? Info;
}

function groupLabel(group: string): string {
    return GROUP_LABELS[group] ?? group.charAt(0).toUpperCase() + group.slice(1);
}
</script>
