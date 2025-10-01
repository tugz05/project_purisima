<template>
  <StaffLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white p-8 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="p-4 bg-white/20 rounded-2xl backdrop-blur-sm shadow-lg">
              <Bell class="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 class="text-3xl font-bold mb-2">Notifications</h1>
              <p class="text-blue-100 text-lg">Stay updated with the latest activities</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Badge variant="secondary" class="bg-white/20 text-white border-white/30 px-4 py-2">
              <Bell class="w-4 h-4 mr-2" />
              {{ unreadCount }} Unread
            </Badge>
            <Button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              :disabled="isMarkingAllRead"
              class="bg-white/20 hover:bg-white/30 text-white border-white/30"
            >
              <CheckCircle class="w-4 h-4 mr-2" />
              {{ isMarkingAllRead ? 'Marking...' : 'Mark All Read' }}
            </Button>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <Label class="text-sm font-medium text-gray-700">Filter by:</Label>
            <Select v-model="selectedFilter">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="All" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Notifications</SelectItem>
                <SelectItem value="unread">Unread Only</SelectItem>
                <SelectItem value="read">Read Only</SelectItem>
                <SelectItem value="transaction">Transactions</SelectItem>
                <SelectItem value="system">System</SelectItem>
                <SelectItem value="announcement">Announcements</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex items-center gap-2">
            <Label class="text-sm font-medium text-gray-700">Priority:</Label>
            <Select v-model="selectedPriority">
              <SelectTrigger class="w-32">
                <SelectValue placeholder="All" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="urgent">Urgent</SelectItem>
                <SelectItem value="high">High</SelectItem>
                <SelectItem value="normal">Normal</SelectItem>
                <SelectItem value="low">Low</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex-1"></div>

          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              @click="refreshNotifications"
              :disabled="isLoading"
              class="flex items-center gap-2"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
              Refresh
            </Button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="bg-white rounded-xl shadow-lg border border-gray-100">
        <div v-if="isLoading" class="p-8 text-center">
          <Loader2 class="w-8 h-8 animate-spin mx-auto text-gray-400 mb-4" />
          <p class="text-gray-600">Loading notifications...</p>
        </div>

        <div v-else-if="filteredNotifications.length === 0" class="p-8 text-center">
          <Bell class="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No notifications</h3>
          <p class="text-gray-600">You're all caught up! No notifications to show.</p>
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="notification in filteredNotifications"
            :key="notification.id"
            class="p-6 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-blue-50 border-l-4 border-l-blue-500': !notification.is_read }"
          >
            <div class="flex items-start gap-4">
              <!-- Icon -->
              <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                :class="getPriorityColor(notification.priority)"
              >
                <component
                  :is="getCategoryIcon(notification.category)"
                  class="w-5 h-5"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <h3 class="text-lg font-semibold text-gray-900">
                        {{ notification.title }}
                      </h3>
                      <Badge
                        :variant="getPriorityVariant(notification.priority)"
                        class="text-xs"
                      >
                        {{ notification.priority.toUpperCase() }}
                      </Badge>
                      <Badge
                        v-if="!notification.is_read"
                        variant="default"
                        class="text-xs bg-blue-600"
                      >
                        NEW
                      </Badge>
                    </div>

                    <p class="text-gray-700 mb-3">{{ notification.message }}</p>

                    <!-- Additional Data -->
                    <div v-if="notification.data" class="text-sm text-gray-600 mb-3">
                      <div v-if="notification.data.transaction_id" class="flex items-center gap-2">
                        <FileText class="w-4 h-4" />
                        <span>Transaction #{{ notification.data.transaction_id }}</span>
                      </div>
                      <div v-if="notification.data.resident_name" class="flex items-center gap-2">
                        <User class="w-4 h-4" />
                        <span>{{ notification.data.resident_name }}</span>
                      </div>
                      <div v-if="notification.data.document_type" class="flex items-center gap-2">
                        <FileText class="w-4 h-4" />
                        <span>{{ notification.data.document_type }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between mt-4">
                  <div class="flex items-center gap-4">
                    <p class="text-sm text-gray-500">
                      {{ notification.time_ago }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ formatDate(notification.created_at) }}
                    </p>
                  </div>

                  <div class="flex items-center gap-2">
                    <Button
                      v-if="!notification.is_read"
                      variant="outline"
                      size="sm"
                      @click="markAsRead(notification)"
                      class="text-blue-600 hover:text-blue-700"
                    >
                      <CheckCircle class="w-4 h-4 mr-1" />
                      Mark Read
                    </Button>

                    <Button
                      v-if="notification.type.startsWith('transaction_') && notification.data?.transaction_id"
                      variant="outline"
                      size="sm"
                      @click="viewTransaction(notification.data.transaction_id)"
                      class="text-green-600 hover:text-green-700"
                    >
                      <Eye class="w-4 h-4 mr-1" />
                      View Transaction
                    </Button>

                    <Button
                      variant="outline"
                      size="sm"
                      @click="deleteNotification(notification)"
                      class="text-red-600 hover:text-red-700"
                    >
                      <Trash2 class="w-4 h-4 mr-1" />
                      Delete
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.data && notifications.data.length > 0" class="p-6 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">
              Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} notifications
            </p>

            <div class="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="loadPage(notifications.prev_page_url)"
                :disabled="!notifications.prev_page_url"
              >
                <ChevronLeft class="w-4 h-4 mr-1" />
                Previous
              </Button>

              <Button
                variant="outline"
                size="sm"
                @click="loadPage(notifications.next_page_url)"
                :disabled="!notifications.next_page_url"
              >
                Next
                <ChevronRight class="w-4 h-4 ml-1" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Bell,
  CheckCircle,
  Loader2,
  RefreshCw,
  Trash2,
  FileText,
  Settings,
  User,
  Eye,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

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

interface Props {
  notifications: {
    data: Notification[];
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  unreadCount: number;
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
  { title: 'Dashboard', href: '/staff/dashboard' },
  { title: 'Notifications', href: '/staff/notifications' },
]);

const isLoading = ref(false);
const isMarkingAllRead = ref(false);
const selectedFilter = ref('all');
const selectedPriority = ref('all');

const filteredNotifications = computed(() => {
  let filtered = props.notifications.data;

  // Filter by read status
  if (selectedFilter.value === 'unread') {
    filtered = filtered.filter(n => !n.is_read);
  } else if (selectedFilter.value === 'read') {
    filtered = filtered.filter(n => n.is_read);
  } else if (selectedFilter.value !== 'all') {
    filtered = filtered.filter(n => n.category === selectedFilter.value);
  }

  // Filter by priority
  if (selectedPriority.value !== 'all') {
    filtered = filtered.filter(n => n.priority === selectedPriority.value);
  }

  return filtered;
});

const markAsRead = async (notification: Notification) => {
  try {
    await router.post(`/staff/notifications/mark-read/${notification.id}`, {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        notification.is_read = true;
        toast.success('Notification marked as read');
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
        props.notifications.data.forEach(notification => {
          notification.is_read = true;
        });
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
  if (!confirm('Are you sure you want to delete this notification?')) {
    return;
  }

  try {
    await router.delete(`/staff/notifications/${notification.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        const index = props.notifications.data.findIndex(n => n.id === notification.id);
        if (index > -1) {
          props.notifications.data.splice(index, 1);
        }
        toast.success('Notification deleted');
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

const viewTransaction = (transactionId: string) => {
  // Check if the transaction ID is a valid number
  if (transactionId && !isNaN(transactionId)) {
    router.visit(`/staff/transactions/${transactionId}`);
  } else {
    // If it's a test notification with invalid ID, just go to transactions index
    router.visit('/staff/transactions');
  }
};

const refreshNotifications = () => {
  isLoading.value = true;
  router.reload({
    onFinish: () => {
      isLoading.value = false;
    },
  });
};

const loadPage = (url: string | null) => {
  if (url) {
    router.visit(url);
  }
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

const getPriorityVariant = (priority: string) => {
  switch (priority) {
    case 'urgent':
      return 'destructive';
    case 'high':
      return 'secondary';
    case 'normal':
      return 'default';
    case 'low':
      return 'outline';
    default:
      return 'outline';
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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>
