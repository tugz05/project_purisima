<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login, home } from '@/routes';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

const termsAccepted = ref(false);
</script>

<template>
    <AuthBase 
        title="Join Uni Respond" 
        description="Create your account to access Uni Respond services, request documents, and report incidents"
    >
        <Head title="Register" />

        <!-- Back to Welcome Page Button -->
        <div class="mb-4">
            <Link 
                :href="home()" 
                class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Home
            </Link>
        </div>

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation', 'terms_accepted']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <input type="hidden" name="terms_accepted" :value="termsAccepted ? '1' : ''" />
            
            <!-- Social Login Buttons -->
            <div class="grid gap-3">
                <Button as-child variant="outline" class="w-full h-11 border-gray-300 hover:bg-gray-50 hover:border-gray-400 transition-colors" tabindex="0">
                    <a href="/auth/google/redirect" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="font-medium">Sign up with Google</span>
                    </a>
                </Button>
                <Button as-child variant="outline" class="w-full h-11 border-gray-300 hover:bg-gray-50 hover:border-gray-400 transition-colors" tabindex="0">
                    <a href="/auth/facebook/redirect" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="#1877F2" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span class="font-medium">Sign up with Facebook</span>
                    </a>
                </Button>
            </div>

            <!-- Divider -->
            <div class="relative my-2">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-gray-300"></span>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-white px-3 text-gray-500 font-medium">Or continue with email</span>
                </div>
            </div>

            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="first_name" class="text-sm font-medium text-gray-700">First Name *</Label>
                    <Input 
                        id="first_name" 
                        type="text" 
                        required 
                        autofocus 
                        :tabindex="1" 
                        autocomplete="given-name" 
                        name="first_name" 
                        placeholder="Enter your first name"
                        class="h-11"
                    />
                    <InputError :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="middle_name" class="text-sm font-medium text-gray-700">Middle Name</Label>
                    <Input 
                        id="middle_name" 
                        type="text" 
                        :tabindex="2" 
                        autocomplete="additional-name" 
                        name="middle_name" 
                        placeholder="Enter your middle name (optional)"
                        class="h-11"
                    />
                    <InputError :message="errors.middle_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name" class="text-sm font-medium text-gray-700">Last Name *</Label>
                    <Input 
                        id="last_name" 
                        type="text" 
                        required 
                        :tabindex="3" 
                        autocomplete="family-name" 
                        name="last_name" 
                        placeholder="Enter your last name"
                        class="h-11"
                    />
                    <InputError :message="errors.last_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="suffix" class="text-sm font-medium text-gray-700">Suffix</Label>
                    <Input 
                        id="suffix" 
                        type="text" 
                        :tabindex="4" 
                        name="suffix" 
                        placeholder="Jr., Sr., II, III, etc. (optional)"
                        class="h-11"
                    />
                    <InputError :message="errors.suffix" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-medium text-gray-700">Email address</Label>
                    <Input 
                        id="email" 
                        type="email" 
                        required 
                        :tabindex="5" 
                        autocomplete="email" 
                        name="email" 
                        placeholder="email@example.com"
                        class="h-11"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-sm font-medium text-gray-700">Password</Label>
                    <Input 
                        id="password" 
                        type="password" 
                        required 
                        :tabindex="6" 
                        autocomplete="new-password" 
                        name="password" 
                        placeholder="Create a strong password"
                        class="h-11"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="7"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Re-enter your password"
                        class="h-11"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <!-- Terms and Conditions Agreement -->
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="terms_accepted"
                        v-model="termsAccepted"
                        name="terms_accepted"
                        :tabindex="8"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                    />
                    <Label 
                        for="terms_accepted" 
                        class="text-xs text-gray-600 cursor-pointer"
                    >
                        I agree to the 
                        <a href="#" class="text-blue-600 hover:text-blue-700 underline" target="_blank" @click.prevent>Terms</a>
                        and 
                        <a href="#" class="text-blue-600 hover:text-blue-700 underline" target="_blank" @click.prevent>Data Privacy Act</a>
                    </Label>
                </div>
                <InputError :message="errors.terms_accepted" />

                <Button 
                    type="submit" 
                    class="mt-2 w-full h-11 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white shadow-lg font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" 
                    tabindex="9" 
                    :disabled="processing || !termsAccepted"
                >
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin mr-2" />
                    {{ processing ? 'Creating account...' : 'Create account' }}
                </Button>
            </div>

            <div class="text-center text-sm text-gray-600 pt-2">
                Already have an account?
                <TextLink :href="login()" class="text-blue-600 hover:text-blue-700 font-medium underline underline-offset-4 transition-colors" :tabindex="7">Log in</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
