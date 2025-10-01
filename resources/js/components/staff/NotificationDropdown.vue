<template>
  <div class="relative" v-if="isStaff">
    <!-- Notification Bell Button -->
    <Button
      variant="ghost"
      size="sm"
      @click="toggleDropdown"
      class="relative p-2 hover:bg-gray-100 rounded-full transition-colors"
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

    <!-- Notification Dropdown -->
    <div
      v-if="isDropdownOpen"
      class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
    >
      <!-- Header -->
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

      <!-- Notifications List -->
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
              <!-- Icon -->
              <div
                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                :class="getPriorityColor(notification.priority)"
              >
                <component
                  :is="getCategoryIcon(notification.category)"
                  class="w-4 h-4"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ notification.title }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                      {{ notification.message }}
                    </p>
                  </div>

                  <!-- Unread indicator -->
                  <div
                    v-if="!notification.is_read"
                    class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full ml-2"
                  ></div>
                </div>

                <!-- Time and Actions -->
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

      <!-- Footer -->
      <div v-if="notifications.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
        <Button
          variant="outline"
          size="sm"
          @click="goToNotifications"
          class="w-full text-sm"
        >
          View All Notifications
        </Button>
      </div>
    </div>

    <!-- Overlay to close dropdown -->
    <div
      v-if="isDropdownOpen"
      class="fixed inset-0 z-40"
      @click="closeDropdown"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Bell,
  CheckCircle,
  Loader2,
  Trash2,
  FileText,
  Settings,
  User,
} from 'lucide-vue-next';

interface Notification {
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

const isDropdownOpen = ref(false);
const isLoading = ref(false);
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isMarkingAllRead = ref(false);

// Check if user is staff - if not, don't initialize
const isStaff = ref(false);

// Detect if user is staff
const checkUserRole = () => {
  try {
    const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
    const userData = window.page?.props?.auth?.user;
    isStaff.value = userRole === 'staff' || userData?.role === 'staff' || userData?.role === 'admin';
  } catch (e) {
    console.log('Could not detect user role, defaulting to non-staff');
    isStaff.value = false;
  }
};

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
  if (isDropdownOpen.value) {
    loadNotifications();
  }
};

const closeDropdown = () => {
  isDropdownOpen.value = false;
};

const loadNotifications = async () => {
  if (!isStaff.value) return;

  isLoading.value = true;
  try {
    const response = await fetch('/staff/notifications/unread');
    const data = await response.json();
    notifications.value = data.notifications;
    unreadCount.value = data.unreadCount;
  } catch (error) {
    console.error('Failed to load notifications:', error);
    toast.error('Failed to load notifications');
  } finally {
    isLoading.value = false;
  }
};

const loadUnreadCount = async () => {
  if (!isStaff.value) return;

  try {
    const response = await fetch('/staff/notifications/count');
    const data = await response.json();
    unreadCount.value = data.count;
  } catch (error) {
    console.error('Failed to load notification count:', error);
  }
};

const handleNotificationClick = (notification: Notification) => {
  if (!notification.is_read) {
    markAsRead(notification);
  }

  // Navigate based on notification type
  if (notification.type.startsWith('transaction_') && notification.data?.transaction_id) {
    // Check if the transaction ID is a valid number
    const transactionId = notification.data.transaction_id;
    if (transactionId && !isNaN(transactionId)) {
      router.visit(`/staff/transactions/${transactionId}`);
    } else {
      // If it's a test notification with invalid ID, just go to transactions index
      router.visit('/staff/transactions');
    }
  }

  closeDropdown();
};

const markAsRead = async (notification: Notification) => {
  try {
    await router.post(`/staff/notifications/mark-read/${notification.id}`, {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        notification.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      },
      onError: (errors) => {
        console.error('Failed to mark notification as read:', errors);
        toast.error('Failed to mark notification as read');
      }
    });
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
    toast.error('Failed to mark notification as read');
  }
};

const markAllAsRead = async () => {
  isMarkingAllRead.value = true;
  try {
    await router.post('/staff/notifications/mark-all-read', {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        notifications.value.forEach(notification => {
          notification.is_read = true;
        });
        unreadCount.value = 0;
        toast.success('All notifications marked as read');
      },
      onError: (errors) => {
        console.error('Failed to mark all notifications as read:', errors);
        toast.error('Failed to mark all notifications as read');
      }
    });
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
    toast.error('Failed to mark all notifications as read');
  } finally {
    isMarkingAllRead.value = false;
  }
};

const deleteNotification = async (notification: Notification) => {
  try {
    await router.delete(`/staff/notifications/${notification.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        const index = notifications.value.findIndex(n => n.id === notification.id);
        if (index > -1) {
          notifications.value.splice(index, 1);
          if (!notification.is_read) {
            unreadCount.value = Math.max(0, unreadCount.value - 1);
          }
        }
      },
      onError: (errors) => {
        console.error('Failed to delete notification:', errors);
        toast.error('Failed to delete notification');
      }
    });
  } catch (error) {
    console.error('Failed to delete notification:', error);
    toast.error('Failed to delete notification');
  }
};

const goToNotifications = () => {
  router.visit('/staff/notifications');
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

// Poll for new notifications every 30 seconds
let pollInterval: number | null = null;
let echoChannel: any = null;

onMounted(() => {
  checkUserRole();
  if (!isStaff.value) return;

  loadUnreadCount();
  pollInterval = setInterval(loadUnreadCount, 30000);

  try {
    const page = usePage();
    const uid = (page as any)?.props?.auth?.user?.id;
    if ((window as any).Echo && uid) {
      echoChannel = (window as any).Echo.private(`App.Models.User.${uid}`);
      echoChannel.listen('.message.sent', (e: any) => {
        // Ignore messages sent by self
        if (e?.message?.sender?.id && e.message.sender.id === uid) return;
        unreadCount.value = (unreadCount.value || 0) + 1;
        const badge = document.getElementById('header-unread-badge');
        if (badge) {
          const current = parseInt(badge.textContent || '0', 10) || 0;
          badge.textContent = String(current + 1);
          (badge as HTMLElement).style.display = 'inline-flex';
        }
      });
    }
  } catch (_e) {}
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
  try {
    if (echoChannel && (window as any).Echo) {
      (window as any).Echo.leave(echoChannel.name || echoChannel.channel || '');
    }
  } catch (_e) {}
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
