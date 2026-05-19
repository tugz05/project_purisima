<template>
  <div class="relative" v-if="isResident">
    <Button
      variant="ghost"
      size="sm"
      @click="toggleDropdown"
      class="relative p-2 hover:bg-gray-100 rounded-full transition-colors"
      aria-label="Notifications"
    >
      <Bell class="w-5 h-5 text-gray-600" />
      <Badge
        v-if="unreadCount > 0"
        variant="destructive"
        class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center text-xs font-bold"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </Badge>
    </Button>

    <div
      v-if="isDropdownOpen"
      class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
    >
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
          <div class="flex items-center gap-2">
            <Button
              v-if="unreadCount > 0"
              variant="outline"
              size="sm"
              @click="markAllAsRead"
              :disabled="isMarkingAllRead"
              class="text-xs"
            >
              <CheckCircle class="w-3 h-3 mr-1" />
              Mark All Read
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="goToNotifications"
              class="text-xs text-blue-600 hover:text-blue-700"
            >
              View All
            </Button>
          </div>
        </div>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <div v-if="isLoading" class="p-4 text-center">
          <Loader2 class="w-6 h-6 animate-spin mx-auto text-gray-400" />
          <p class="text-sm text-gray-500 mt-2">Loading notifications...</p>
        </div>

        <div v-else-if="notifications.length === 0" class="p-4 text-center">
          <Bell class="w-8 h-8 mx-auto text-gray-300 mb-2" />
          <p class="text-sm text-gray-500">No notifications</p>
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
            :class="{ 'bg-blue-50': !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start gap-3">
              <div
                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                :class="getPriorityColor(notification.priority)"
              >
                <component :is="getCategoryIcon(notification.category)" class="w-4 h-4" />
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ notification.title }}</p>
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
                  </div>
                  <div v-if="!notification.is_read" class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full ml-2"></div>
                </div>

                <div class="flex items-center justify-between mt-2">
                  <p class="text-xs text-gray-500">{{ notification.time_ago }}</p>
                  <div class="flex items-center gap-1">
                    <Button
                      v-if="!notification.is_read"
                      variant="ghost"
                      size="sm"
                      @click.stop="markAsRead(notification)"
                      class="text-xs text-blue-600 hover:text-blue-700 p-1 h-auto"
                    >
                      Mark Read
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click.stop="deleteNotification(notification)"
                      class="text-xs text-red-600 hover:text-red-700 p-1 h-auto"
                    >
                      <Trash2 class="w-3 h-3" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="notifications.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
        <Button variant="outline" size="sm" @click="goToNotifications" class="w-full">
          View all notifications
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { laravelJsonFetch } from '@/utils/laravelJsonFetch';
import { useJsonActionFeedback } from '@/composables/useJsonActionFeedback';
import { getPusher } from '@/pusher';
import { Bell, CheckCircle, FileText, Loader2, Settings, Trash2, User } from 'lucide-vue-next';
import { playNotificationSound } from '@/composables/useInAppAlertSounds';

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
}

const page = usePage() as any;
const currentUser = computed(() => page?.props?.auth?.user || null);
const isResident = computed(() => currentUser.value && currentUser.value.role === 'resident');

const isDropdownOpen = ref(false);
const isLoading = ref(false);
const isMarkingAllRead = ref(false);
const notifications = ref<NotificationRow[]>([]);
const unreadCount = ref<number>(Number(page?.props?.residentUnreadNotificationsCount ?? 0) || 0);

const { runJsonAction } = useJsonActionFeedback();

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
  if (isDropdownOpen.value) {
    void loadUnread();
  }
};

const closeDropdown = () => {
  isDropdownOpen.value = false;
};

const loadUnread = async () => {
  if (!isResident.value) return;
  isLoading.value = true;
  try {
    const response = await laravelJsonFetch('/resident/notifications/unread', { method: 'GET' });
    if (response.ok) {
      const data = await response.json();
      notifications.value = data.notifications || [];
      unreadCount.value = Number(data.unreadCount) || 0;
    }
  } finally {
    isLoading.value = false;
  }
};

const loadCount = async () => {
  if (!isResident.value) return;
  try {
    const response = await laravelJsonFetch('/resident/notifications/count', { method: 'GET' });
    if (response.ok) {
      const data = await response.json();
      unreadCount.value = Number(data.count) || 0;
    }
  } catch {
    // ignore
  }
};

const markAsRead = async (notification: NotificationRow) => {
  await runJsonAction(
    `/resident/notifications/mark-read/${notification.id}`,
    { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' },
    {
      successToast: false,
      onSuccess: () => {
        notification.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      },
    },
  );
};

const markAllAsRead = async () => {
  isMarkingAllRead.value = true;
  try {
    await runJsonAction(
      '/resident/notifications/mark-all-read',
      { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' },
      {
        successToast: false,
        onSuccess: () => {
          notifications.value.forEach((n) => (n.is_read = true));
          unreadCount.value = 0;
        },
      },
    );
  } finally {
    isMarkingAllRead.value = false;
  }
};

const deleteNotification = async (notification: NotificationRow) => {
  await runJsonAction(
    `/resident/notifications/${notification.id}`,
    { method: 'DELETE' },
    {
      successToast: false,
      onSuccess: () => {
        const idx = notifications.value.findIndex((n) => n.id === notification.id);
        if (idx !== -1) {
          const wasUnread = !notifications.value[idx].is_read;
          notifications.value.splice(idx, 1);
          if (wasUnread) unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
      },
    },
  );
};

const handleNotificationClick = (notification: NotificationRow) => {
  if (!notification.is_read) {
    void markAsRead(notification);
  }
  const txId = notification?.data?.transaction_id;
  if (notification.type?.startsWith('transaction_') && txId) {
    router.visit(`/resident/transactions/${txId}`);
    closeDropdown();
  }
};

const goToNotifications = () => {
  router.visit('/resident/notifications');
  closeDropdown();
};

const getPriorityColor = (priority: string) => {
  switch (priority) {
    case 'urgent':
      return 'bg-red-100 text-red-600';
    case 'high':
      return 'bg-orange-100 text-orange-600';
    case 'normal':
      return 'bg-blue-100 text-blue-600';
    case 'low':
      return 'bg-gray-100 text-gray-600';
    default:
      return 'bg-gray-100 text-gray-600';
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

let pollInterval: ReturnType<typeof setInterval> | null = null;
let echoChannelName = '';

const stopRealtime = () => {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
  try {
    const pusher = getPusher();
    if (echoChannelName && pusher) {
      pusher.unsubscribe(echoChannelName);
    }
  } catch {}
  echoChannelName = '';
};

const startRealtime = () => {
  stopRealtime();
  void loadCount();
  pollInterval = setInterval(() => {
    void loadCount();
  }, 30000);

  try {
    const uid = currentUser.value?.id;
    const pusher = getPusher();
    if (pusher && uid) {
      echoChannelName = `private-App.Models.User.${uid}`;
      const channel = pusher.subscribe(echoChannelName);
      channel.bind('notification.created', () => {
        playNotificationSound();
        void loadCount();
        if (isDropdownOpen.value) void loadUnread();
      });
    }
  } catch {}
};

watch(isResident, (resident) => {
  if (resident) startRealtime();
  else stopRealtime();
}, { immediate: true });

onUnmounted(() => {
  stopRealtime();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
