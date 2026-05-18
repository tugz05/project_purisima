<template>
  <StaffLayout :breadcrumbs="staffSmsBroadcastBreadcrumbs">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-teal-50">
      <!-- Header -->
      <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-teal-600 to-emerald-600 shadow-xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full -translate-x-36 -translate-y-36"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-48 translate-y-48"></div>
        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="text-white">
              <div class="flex items-center gap-4 mb-2">
                <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                  <MessageSquare class="h-8 w-8" />
                </div>
                <div>
                  <h1 class="text-4xl font-bold">SMS Broadcast</h1>
                  <p class="text-green-100 text-lg mt-1">Send SMS messages to all residents with phone numbers</p>
                </div>
              </div>
            </div>
            <Button
              @click="composerOpen = true"
              class="bg-white text-green-700 hover:bg-green-50 shadow-lg hover:shadow-xl transition-all duration-300"
            >
              <Send class="w-4 h-4 mr-2" />
              New Broadcast
            </Button>
          </div>
        </div>
      </div>

      <!-- Compose Sheet -->
      <Sheet v-model:open="composerOpen">
        <SheetContent class="w-full sm:max-w-lg">
          <SheetHeader>
            <SheetTitle class="flex items-center gap-2">
              <Send class="w-5 h-5 text-green-600" />
              New SMS Broadcast
            </SheetTitle>
            <SheetDescription>
              This message will be sent to every resident who has a phone number on file. Messages are queued and sent asynchronously.
            </SheetDescription>
          </SheetHeader>

          <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div class="space-y-1.5">
              <Label for="title">Title / Subject</Label>
              <Input
                id="title"
                v-model="form.title"
                placeholder="e.g. Barangay Health Alert"
                :class="{ 'border-red-500': form.errors.title }"
              />
              <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="message">
                Message
                <span class="ml-auto float-right text-xs text-gray-400">{{ form.message.length }} / 1600</span>
              </Label>
              <Textarea
                id="message"
                v-model="form.message"
                rows="6"
                maxlength="1600"
                placeholder="Type your message here…"
                :class="{ 'border-red-500': form.errors.message }"
              />
              <p v-if="form.errors.message" class="text-xs text-red-500">{{ form.errors.message }}</p>
              <p class="text-xs text-gray-500">
                Standard SMS is 160 chars. Longer messages are split automatically by the carrier.
              </p>
            </div>

            <div class="flex gap-3 pt-2">
              <Button type="submit" :disabled="form.processing" class="flex-1 bg-green-600 hover:bg-green-700">
                <Send class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Sending…' : 'Send Broadcast' }}
              </Button>
              <Button type="button" variant="outline" @click="composerOpen = false">Cancel</Button>
            </div>
          </form>
        </SheetContent>
      </Sheet>

      <!-- Broadcast History -->
      <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
              <Clock class="w-5 h-5 text-gray-500" />
              Broadcast History
            </h2>
          </div>

          <div v-if="broadcasts.data.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <MessageSquare class="w-12 h-12 mb-3 opacity-40" />
            <p class="text-base font-medium">No broadcasts yet</p>
            <p class="text-sm">Use the button above to send your first SMS broadcast.</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Message</TableHead>
                <TableHead class="text-center">Recipients</TableHead>
                <TableHead class="text-center">Sent</TableHead>
                <TableHead class="text-center">Failed</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Sent by</TableHead>
                <TableHead>Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="b in broadcasts.data" :key="b.id" class="hover:bg-gray-50">
                <TableCell class="font-medium max-w-[160px] truncate">{{ b.title }}</TableCell>
                <TableCell class="max-w-[220px]">
                  <p class="truncate text-sm text-gray-600" :title="b.message">{{ b.message }}</p>
                </TableCell>
                <TableCell class="text-center">{{ b.recipients_count }}</TableCell>
                <TableCell class="text-center text-green-600 font-medium">{{ b.sent_count }}</TableCell>
                <TableCell class="text-center text-red-500 font-medium">{{ b.failed_count }}</TableCell>
                <TableCell>
                  <Badge :class="statusClass(b.status)">{{ b.status }}</Badge>
                </TableCell>
                <TableCell class="text-sm text-gray-600">{{ b.creator?.name ?? '—' }}</TableCell>
                <TableCell class="text-sm text-gray-500 whitespace-nowrap">{{ formatDate(b.created_at) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="broadcasts.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
            <p class="text-sm text-gray-500">
              Showing {{ broadcasts.from }}–{{ broadcasts.to }} of {{ broadcasts.total }}
            </p>
            <div class="flex gap-2">
              <Button
                v-if="broadcasts.prev_page_url"
                variant="outline" size="sm"
                @click="router.visit(broadcasts.prev_page_url)"
              >
                Previous
              </Button>
              <Button
                v-if="broadcasts.next_page_url"
                variant="outline" size="sm"
                @click="router.visit(broadcasts.next_page_url)"
              >
                Next
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { MessageSquare, Send, Clock } from 'lucide-vue-next';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription,
} from '@/components/ui/sheet';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

interface Creator {
  id: number;
  name: string;
}

interface Broadcast {
  id: number;
  title: string;
  message: string;
  recipients_count: number;
  sent_count: number;
  failed_count: number;
  status: string;
  created_at: string;
  creator: Creator | null;
}

interface PaginatedBroadcasts {
  data: Broadcast[];
  total: number;
  from: number;
  to: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}

const props = defineProps<{
  broadcasts: PaginatedBroadcasts;
}>();

const { staffSmsBroadcastBreadcrumbs } = useBreadcrumbs();

const composerOpen = ref(false);

const form = useForm({
  title: '',
  message: '',
});

function submit() {
  form.post(route('staff.sms.store'), {
    onSuccess: () => {
      composerOpen.value = false;
      form.reset();
    },
  });
}

function statusClass(status: string) {
  return {
    'bg-yellow-100 text-yellow-800': status === 'processing',
    'bg-green-100 text-green-800': status === 'completed',
    'bg-red-100 text-red-800': status === 'failed',
    'bg-gray-100 text-gray-700': status === 'pending',
  };
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}
</script>
