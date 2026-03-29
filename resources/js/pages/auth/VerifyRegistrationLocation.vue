<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthBase from '@/layouts/AuthLayout.vue'
import { home } from '@/routes'
import { ArrowLeft } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

defineProps<{
    municipalityLabel: string
}>()

const page = usePage()

const form = useForm({
    latitude: null as number | null,
    longitude: null as number | null,
})

const geoError = ref<string | null>(null)
const locating = ref(false)

const submitCoords = (latitude: number, longitude: number): void => {
    form.latitude = latitude
    form.longitude = longitude
    form.post('/register/verify-location', {
        preserveScroll: true,
        onFinish: () => {
            locating.value = false
        },
    })
}

const requestLocation = (): void => {
    geoError.value = null
    form.clearErrors()

    if (!navigator.geolocation) {
        geoError.value = 'This browser does not support location services.'
        return
    }

    locating.value = true

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            submitCoords(pos.coords.latitude, pos.coords.longitude)
        },
        (err) => {
            locating.value = false
            geoError.value =
                err.code === err.PERMISSION_DENIED
                    ? 'Location permission was denied. Allow location access to verify you are in Tago, Surigao del Sur.'
                    : 'Could not read your location. Check GPS or Wi‑Fi and try again.'
        },
        { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 },
    )
}
</script>

<template>
    <AuthBase
        title="Confirm your location"
        description="For new resident accounts (email or social sign-up) we verify that you are within Tago, Surigao del Sur."
    >
        <Head title="Verify location" />

        <div class="mb-4">
            <Link
                :href="home()"
                class="inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-gray-900"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Home
            </Link>
        </div>

        <div v-if="page.props.flash?.error" class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            {{ page.props.flash.error }}
        </div>

        <div class="space-y-4 text-sm text-gray-600">
            <p>
                Tap the button below and allow location access. We only use this once to confirm you are in
                <strong class="text-gray-900">{{ municipalityLabel }}</strong>. Coordinates are stored with your account for
                barangay services.
            </p>
            <ul class="list-inside list-disc space-y-1 text-xs text-gray-500">
                <li>Enable device location (GPS) for best accuracy.</li>
                <li>If you are visiting from outside the area, registration cannot proceed.</li>
            </ul>
        </div>

        <div class="mt-6 flex flex-col gap-3">
            <Button
                type="button"
                class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg hover:from-blue-700 hover:to-cyan-600"
                :disabled="locating || form.processing"
                @click="requestLocation"
            >
                {{ locating || form.processing ? 'Verifying…' : 'Share my location to continue' }}
            </Button>

            <p v-if="geoError" class="text-center text-sm text-red-600">
                {{ geoError }}
            </p>
            <p v-if="form.errors.location" class="text-center text-sm text-red-600">
                {{ form.errors.location }}
            </p>
            <p v-if="form.errors.latitude" class="text-center text-sm text-red-600">
                {{ form.errors.latitude }}
            </p>
            <p v-if="form.errors.longitude" class="text-center text-sm text-red-600">
                {{ form.errors.longitude }}
            </p>
        </div>

        <div class="mt-8 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
            Wrong Google account?
            <Link href="/logout" method="post" as="button" class="ml-1 font-medium text-blue-600 hover:text-blue-800">
                Sign out
            </Link>
        </div>
    </AuthBase>
</template>
