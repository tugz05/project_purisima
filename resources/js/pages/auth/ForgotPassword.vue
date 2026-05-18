<script setup lang="ts">
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, Smartphone } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout title="Forgot password" description="Enter your email to receive a password reset link">
        <Head title="Forgot password" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="PasswordResetLinkController.store.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" name="email" autocomplete="off" autofocus placeholder="email@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button class="w-full" :disabled="processing">
                        <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                        Email password reset link
                    </Button>
                </div>
            </Form>

            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-gray-200"></span>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-white px-2 text-gray-400">or</span>
                </div>
            </div>

            <TextLink :href="route('password.sms.show')" class="flex items-center justify-center gap-1.5 text-sm">
                <Smartphone class="h-4 w-4" />
                Reset via SMS OTP instead
            </TextLink>

            <div class="space-x-1 text-center text-sm text-muted-foreground mt-4">
                <span>Or, return to</span>
                <TextLink :href="login()">log in</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
