<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Smartphone, KeyRound, Lock, ArrowLeft, RefreshCw } from 'lucide-vue-next';

const props = defineProps<{
    step: 'phone' | 'otp' | 'password';
}>();

// ── Step 1: phone number ──────────────────────────────────────────────────────
const phoneForm = useForm({ phone: '' });
const sendOtp = () => phoneForm.post(route('password.sms.send'));

// ── Step 2: OTP code ──────────────────────────────────────────────────────────
const otpForm = useForm({ code: '' });
const verifyOtp = () => otpForm.post(route('password.sms.verify'));
const resendOtp = () => phoneForm.post(route('password.sms.send'));

// ── Step 3: new password ──────────────────────────────────────────────────────
const passwordForm = useForm({ password: '', password_confirmation: '' });
const resetPassword = () => passwordForm.post(route('password.sms.reset'));
</script>

<template>
    <AuthLayout
        :title="step === 'phone' ? 'Reset via SMS' : step === 'otp' ? 'Enter OTP' : 'New Password'"
        :description="
            step === 'phone'
                ? 'Enter your registered phone number to receive a one-time code'
                : step === 'otp'
                ? 'A 6-digit code was sent to your phone. Enter it below.'
                : 'Choose a strong new password for your account'
        "
    >
        <Head title="Forgot password" />

        <!-- Step indicator -->
        <div class="flex items-center justify-center gap-2 mb-6">
            <div
                v-for="(label, i) in ['Phone', 'OTP', 'Password']"
                :key="i"
                class="flex items-center gap-2"
            >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                    :class="
                        (step === 'phone' && i === 0) ||
                        (step === 'otp' && i === 1) ||
                        (step === 'password' && i === 2)
                            ? 'bg-blue-600 text-white'
                            : (step === 'otp' && i === 0) || (step === 'password' && i <= 1)
                            ? 'bg-green-500 text-white'
                            : 'bg-gray-200 text-gray-500'
                    "
                >
                    {{ i + 1 }}
                </div>
                <span
                    class="text-xs hidden sm:block"
                    :class="
                        (step === 'phone' && i === 0) ||
                        (step === 'otp' && i === 1) ||
                        (step === 'password' && i === 2)
                            ? 'text-blue-600 font-medium'
                            : 'text-gray-400'
                    "
                >{{ label }}</span>
                <div v-if="i < 2" class="h-px w-6 bg-gray-300 hidden sm:block" />
            </div>
        </div>

        <!-- ── STEP 1: Phone ─────────────────────────────────────────────────── -->
        <form v-if="step === 'phone'" class="space-y-5" @submit.prevent="sendOtp">
            <div class="grid gap-2">
                <Label for="phone" class="flex items-center gap-1.5">
                    <Smartphone class="h-3.5 w-3.5" />
                    Phone Number
                </Label>
                <Input
                    id="phone"
                    v-model="phoneForm.phone"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="09XXXXXXXXX or +63XXXXXXXXX"
                    autofocus
                    :class="{ 'border-red-500': phoneForm.errors.phone }"
                />
                <InputError :message="phoneForm.errors.phone" />
            </div>

            <Button type="submit" class="w-full" :disabled="phoneForm.processing">
                <LoaderCircle v-if="phoneForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                <Smartphone v-else class="mr-2 h-4 w-4" />
                {{ phoneForm.processing ? 'Sending code…' : 'Send OTP' }}
            </Button>

            <div class="text-center text-sm text-muted-foreground space-y-1">
                <div>
                    <TextLink :href="route('password.request')">
                        <ArrowLeft class="inline h-3 w-3 mr-1" />Reset via email instead
                    </TextLink>
                </div>
                <div>
                    <TextLink :href="route('login')">Back to log in</TextLink>
                </div>
            </div>
        </form>

        <!-- ── STEP 2: OTP ───────────────────────────────────────────────────── -->
        <form v-else-if="step === 'otp'" class="space-y-5" @submit.prevent="verifyOtp">
            <div class="grid gap-2">
                <Label for="code" class="flex items-center gap-1.5">
                    <KeyRound class="h-3.5 w-3.5" />
                    6-Digit Code
                </Label>
                <Input
                    id="code"
                    v-model="otpForm.code"
                    type="text"
                    inputmode="numeric"
                    pattern="\d{6}"
                    maxlength="6"
                    placeholder="000000"
                    autofocus
                    class="text-center text-2xl tracking-[0.5em] font-mono"
                    :class="{ 'border-red-500': otpForm.errors.code }"
                />
                <InputError :message="otpForm.errors.code" />
                <p class="text-xs text-gray-500 text-center">
                    Code is valid for 10 minutes.
                </p>
            </div>

            <Button type="submit" class="w-full" :disabled="otpForm.processing">
                <LoaderCircle v-if="otpForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                <KeyRound v-else class="mr-2 h-4 w-4" />
                {{ otpForm.processing ? 'Verifying…' : 'Verify Code' }}
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Didn't receive it?
                <button
                    type="button"
                    class="text-blue-600 hover:underline inline-flex items-center gap-1 ml-1"
                    :disabled="phoneForm.processing"
                    @click="resendOtp"
                >
                    <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': phoneForm.processing }" />
                    Resend
                </button>
            </div>
        </form>

        <!-- ── STEP 3: New Password ──────────────────────────────────────────── -->
        <form v-else class="space-y-5" @submit.prevent="resetPassword">
            <div class="grid gap-2">
                <Label for="password" class="flex items-center gap-1.5">
                    <Lock class="h-3.5 w-3.5" />
                    New Password
                </Label>
                <Input
                    id="password"
                    v-model="passwordForm.password"
                    type="password"
                    autocomplete="new-password"
                    autofocus
                    placeholder="Min. 8 characters"
                    :class="{ 'border-red-500': passwordForm.errors.password }"
                />
                <InputError :message="passwordForm.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <Input
                    id="password_confirmation"
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Repeat new password"
                    :class="{ 'border-red-500': passwordForm.errors.password_confirmation }"
                />
                <InputError :message="passwordForm.errors.password_confirmation" />
            </div>

            <Button type="submit" class="w-full" :disabled="passwordForm.processing">
                <LoaderCircle v-if="passwordForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ passwordForm.processing ? 'Resetting…' : 'Reset Password' }}
            </Button>
        </form>
    </AuthLayout>
</template>
