<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import ResidentLayout from '@/layouts/resident/Layout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    MessageSquare,
    Search,
    Plus,
    ArrowLeft,
    Send,
    Users,
    UserCheck,
    CheckCircle
} from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    staffUsers: User[];
}

const props = defineProps<Props>();

const { createBreadcrumbs } = useBreadcrumbs();
const breadcrumbs = createBreadcrumbs([
    { title: 'Home', href: '/resident/dashboard' },
    { title: 'Messages', href: '/resident/messaging' },
    { title: 'New Conversation', href: '/resident/messaging/create' },
]);

// Form data
const selectedStaffId = ref<number | null>(null);
const subject = ref('');
const content = ref('');
const isLoading = ref(false);
const searchQuery = ref('');

// Computed properties
const selectedStaff = computed(() =>
    props.staffUsers.find(user => user.id === selectedStaffId.value)
);

const filteredStaffUsers = computed(() => {
    if (!searchQuery.value) return props.staffUsers;
    return props.staffUsers.filter(user =>
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const canSubmit = computed(() => {
    return selectedStaffId.value && content.value.trim().length > 0;
});

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const selectStaff = (staffId: number) => {
    selectedStaffId.value = staffId;
};

const createConversation = async () => {
    if (!canSubmit.value || isLoading.value) return;

    isLoading.value = true;

    try {
        await router.post('/resident/messaging', {
            staff_id: selectedStaffId.value,
            subject: subject.value,
            content: content.value,
        }, {
            onSuccess: () => {
                // Navigate to the new conversation
                // The backend will redirect to the conversation
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    } catch (error) {
        console.error('Failed to create conversation:', error);
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Start New Conversation" />

    <ResidentLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="space-y-4">
                <Link href="/resident/messaging" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors mb-4">
                    <ArrowLeft class="h-4 w-4" />
                    <span class="text-sm font-medium">Back to Messages</span>
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Start New Conversation</h1>
                <p class="text-gray-600 mt-2">Contact a staff member to get help with your concerns or questions.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Left Side: Staff Selection -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Select Staff Member
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Search -->
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search staff members..."
                                    class="pl-10 rounded-full border-gray-300 focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <!-- Staff List -->
                            <div class="space-y-3 max-h-80 overflow-y-auto">
                                <div
                                    v-if="filteredStaffUsers.length === 0"
                                    class="text-center py-8 text-gray-500"
                                >
                                    <p>No staff members found</p>
                                </div>

                                <div
                                    v-else
                                    v-for="staff in filteredStaffUsers"
                                    :key="staff.id"
                                    :class="[
                                        'flex items-center gap-3 p-4 rounded-xl cursor-pointer transition-all duration-200 border-2',
                                        selectedStaffId === staff.id
                                            ? 'border-green-500 bg-green-50 shadow-md'
                                            : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
                                    ]"
                                    @click="selectStaff(staff.id)"
                                >
                                    <!-- Staff Avatar -->
                                    <div class="relative">
                                        <Avatar class="h-12 w-12 ring-2 ring-white shadow-sm">
                                            <AvatarImage :src="`https://ui-avatars.com/api/?name=${staff.name}&background=10b981&color=fff&bold=true`" />
                                            <AvatarFallback class="font-semibold">{{ getInitials(staff.name) }}</AvatarFallback>
                                        </Avatar>
                                        <!-- Staff indicator -->
                                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-500 border-2 border-white rounded-full"></div>
                                    </div>

                                    <!-- Staff Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold text-gray-900">{{ staff.name }}</h3>
                                            <Badge variant="outline" class="text-xs">Staff</Badge>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ staff.email }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Available for conversation</p>
                                    </div>

                                    <!-- Selection indicator -->
                                    <div
                                        :class="[
                                            'w-5 h-5 rounded-full border-2 transition-colors',
                                            selectedStaffId === staff.id
                                                ? 'border-green-500 bg-green-500'
                                                : 'border-gray-300'
                                        ]"
                                    >
                                        <CheckCircle v-if="selectedStaffId === staff.id" class="w-full h-full text-white p-0.5" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Side: Message Composition -->
                <div class="space-y-6">
                    <!-- Selected Staff Info -->
                    <div v-if="selectedStaff" class="space-y-4">
                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-14 w-14 ring-2 ring-white shadow-md">
                                        <AvatarImage :src="`https://ui-avatars.com/api/?name=${selectedStaff.name}&background=10b981&color=fff&bold=true`" />
                                        <AvatarFallback class="font-bold text-lg">{{ getInitials(selectedStaff.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h3 class="font-bold text-gray-900 text-lg">{{ selectedStaff.name }}</h3>
                                        <p class="text-gray-600">{{ selectedStaff.email }}</p>
                                        <Badge variant="outline" class="text-xs mt-1">Barangay Staff</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Message Form -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                Compose Your Message
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Subject -->
                            <div>
                                <Label for="subject" class="text-sm font-medium">Subject (Optional)</Label>
                                <Input
                                    id="subject"
                                    v-model="subject"
                                    placeholder="Brief description of your concern..."
                                    class="mt-1"
                                />
                            </div>

                            <!-- Message Content -->
                            <div>
                                <Label for="content" class="text-sm font-medium">Message *</Label>
                                <Textarea
                                    id="content"
                                    v-model="content"
                                    placeholder="Write your message here. Be as detailed as possible to help staff understand your concern or question..."
                                    rows="6"
                                    class="mt-1 resize-none"
                                    maxlength="5000"
                                />
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>Be clear and specific about what you need help with</span>
                                    <span>{{ content.length }}/5000</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <Button
                                    @click="createConversation"
                                    :disabled="!canSubmit || isLoading"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white py-3"
                                    size="lg"
                                >
                                    <div v-if="isLoading" class="flex items-center justify-center gap-2">
                                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                        <span>Starting conversation...</span>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2">
                                        <Send class="h-4 w-4" />
                                        <span>{{ selectedStaff ? `Message ${selectedStaff.name}` : 'Select Staff First' }}</span>
                                    </div>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Help Text -->
            <Card class="mt-8 bg-blue-50 border-blue-200">
                <CardContent class="p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <UserCheck class="h-4 w-4 text-white" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-900 mb-2">Need help choosing a staff member?</h4>
                            <div class="text-blue-800 text-sm space-y-1">
                                <p>• <strong>General inquiries:</strong> Any available staff member can help</p>
                                <p>• <strong>Transaction questions:</strong> Staff can process documents and payments</p>
                                <p>• <strong>Services:</strong> Staff can assist with requests</p>
                                <p>• All staff members are trained to help with a variety of barangay concerns</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ResidentLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>
