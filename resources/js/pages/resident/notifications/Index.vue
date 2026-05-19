<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useJsonActionFeedback } from '@/composables/useJsonActionFeedback';
import { Bell, CheckCircle, FileText, RefreshCw, Settings, Trash2, User } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface NotificationRow {
  id: number;
  type: string;
  title: string;
  message: string;
  data: any;
  is_read: boolean;
  priority: string;
  category: string;
  time_ago: string;
  created_at: string;
}

interface Props {
  notifications: {
    data: NotificationRow[];
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
    from: number;
    to: number;
  };
  unreadCount: number;
}

const props = defineProps<Props>();
const { runJsonAction } = useJsonActionFeedback();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/resident/dashboard' },
  { title: 'Notifications', href: '/resident/notifications' },
];

const isLoading = ref(false);
const isMarkingAllRead = ref(false);
const selectedFilter = ref<'all' | 'unread' | 'read' | 'transaction' | 'system' | 'user'>('all');

const filtered = computed(() => {
  let rows = props.notifications.data;
  if (selectedFilter.value === 'unread') rows = rows.filter((n) => !n.is_read);
  else if (selectedFilter.value === 'read') rows = rows.filter((n) => n.is_read);
  else if (selectedFilter.value !== 'all') rows = rows.filter((n) => n.category === selectedFilter.value);
  return rows;
});

const refresh = () => {
  isLoading.value = true;
  router.reload({ onFinish: () => (isLoading.value = false) });
};

const markAsRead = async (n: NotificationRow) => {
  if (n.is_read) return;
  await runJsonAction(
    `/resident/notifications/mark-read/${n.id}`,
    { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' },
    { successToast: false, onSuccess: () => (n.is_read = true) },
  );
};

const markAllAsRead = async () => {
  isMarkingAllRead.value = true;
  try {
    await runJsonAction(
      '/resident/notifications/mark-all-read',
      { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' },
      {
        successToast: 'All notifications marked as read',
        onSuccess: () => {
          props.notifications.data.forEach((n) => (n.is_read = true));
        },
      },
    );
  } finally {
    isMarkingAllRead.value = false;
  }
};

const remove = async (n: NotificationRow) => {
  await runJsonAction(`/resident/notifications/${n.id}`, { method: 'DELETE' }, { successToast: 'Notification deleted' });
  const idx = props.notifications.data.findIndex((x) => x.id === n.id);
  if (idx !== -1) props.notifications.data.splice(idx, 1);
};

const open = async (n: NotificationRow) => {
  await markAsRead(n);
  const txId = n?.data?.transaction_id;
  if (n.type?.startsWith('transaction_') && txId) {
    router.visit(`/resident/transactions/${txId}`);
  }
};

const getCategoryIcon = (category: string) => {
  switch (category) {
    case 'transaction':
      return FileText;
    case 'system':
      return Settings;
    case 'user':
      return User;
    default:
      return Bell;
  }
};
</script>

<template>
  <Head title="Notifications" />

  <ResidentLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-5xl mx-auto space-y-6">
      <Card class="border-0 shadow-xl bg-white/80 backdrop-blur-sm">
        <CardHeader class="flex flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-xl bg-blue-100 flex items-center justify-center">
              <Bell class="h-6 w-6 text-blue-700" />
            </div>
            <div>
              <CardTitle class="text-2xl font-bold text-gray-900">Notifications</CardTitle>
              <p class="text-sm text-gray-600">Stay updated with your requests and account updates.</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Badge class="bg-blue-100 text-blue-800 border-blue-200">
              {{ unreadCount }} unread
            </Badge>
            <Button v-if="unreadCount > 0" variant="outline" @click="markAllAsRead" :disabled="isMarkingAllRead">
              <CheckCircle class="h-4 w-4 mr-2" />
              {{ isMarkingAllRead ? 'Marking...' : 'Mark All Read' }}
            </Button>
            <Button variant="outline" @click="refresh" :disabled="isLoading">
              <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': isLoading }" />
              Refresh
            </Button>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center gap-3">
            <Label class="text-sm font-medium text-gray-700">Filter</Label>
            <Select v-model="selectedFilter">
              <SelectTrigger class="w-56">
                <SelectValue placeholder="All" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="unread">Unread only</SelectItem>
                <SelectItem value="read">Read only</SelectItem>
                <SelectItem value="transaction">Transactions</SelectItem>
                <SelectItem value="system">System</SelectItem>
                <SelectItem value="user">User</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div v-if="filtered.length === 0" class="py-12 text-center text-gray-600">
            <Bell class="h-12 w-12 mx-auto text-gray-300 mb-3" />
            No notifications to show.
          </div>

          <div v-else class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden bg-white">
            <div
              v-for="n in filtered"
              :key="n.id"
              class="p-4 hover:bg-gray-50 transition cursor-pointer"
              :class="{ 'bg-blue-50': !n.is_read }"
              @click="open(n)"
            >
              <div class="flex items-start gap-3">
                <div class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600">
                  <component :is="getCategoryIcon(n.category)" class="h-5 w-5" />
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-900 truncate">{{ n.title }}</p>
                      <p class="text-sm text-gray-700 mt-1">{{ n.message }}</p>
                      <p class="text-xs text-gray-500 mt-2">{{ n.time_ago }}</p>
                    </div>
                    <Button variant="ghost" size="sm" class="text-red-600 hover:text-red-700" @click.stop="remove(n)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-2">
            <Button variant="outline" :disabled="!notifications.prev_page_url" @click="notifications.prev_page_url && router.visit(notifications.prev_page_url)">
              Prev
            </Button>
            <div class="text-sm text-gray-600">
              {{ notifications.from || 0 }}-{{ notifications.to || 0 }} of {{ notifications.total || 0 }}
            </div>
            <Button variant="outline" :disabled="!notifications.next_page_url" @click="notifications.next_page_url && router.visit(notifications.next_page_url)">
              Next
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </ResidentLayout>
</template>
