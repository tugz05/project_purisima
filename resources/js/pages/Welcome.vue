<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {
  Shield, Bell, Activity, FileText, Users, Smartphone, ClipboardCheck,
  Send, AlertTriangle, MapPin, Phone, Mail, Globe, Accessibility, Lock,
  Menu, X, CheckCircle2, Info, Sparkles, User, Calendar, Award, Building2,
  Clock, CheckCircle, Star, Heart, Target, Zap, Globe2, Database, Eye,
  ArrowRight, Navigation, Radio
} from 'lucide-vue-next';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

// Auth + mobile state
const page = usePage();
const isAuthed = computed(() => !!(page.props as any).auth?.user);
const mobileOpen = ref(false);
const toggleMobile = () => (mobileOpen.value = !mobileOpen.value);

// Barangay Officials data
interface BarangayOfficial {
  first_name: string;
  last_name: string;
  middle_name?: string;
  suffix?: string;
  position: string;
  photo?: string | null;
  term_start?: number;
  term_end?: number;
  is_active: boolean;
}

const barangayOfficials = ref<BarangayOfficial[]>([]);
const loadingOfficials = ref(true);

// Announcements data
interface Announcement {
  id: number;
  title: string;
  content: string;
  type: string;
  priority: string;
  is_featured: boolean;
  published_at: string;
  expires_at?: string;
  image_path?: string;
  author_name?: string;
  author_position?: string;
}

const announcements = ref<Announcement[]>([]);
const loadingAnnouncements = ref(true);
const selectedAnnouncement = ref<Announcement | null>(null);

// Fetch Barangay Officials
const fetchOfficials = async () => {
  try {
    const response = await fetch('/api/barangay-officials');

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    barangayOfficials.value = data;

    // If no officials found, show sample data for demo
    if (data.length === 0) {
      barangayOfficials.value = [
        {
          first_name: 'Maria',
          last_name: 'Santos',
          middle_name: 'Cruz',
          suffix: '',
          position: 'Barangay Captain',
          photo: null,
          term_start: 2023,
          term_end: 2026,
          is_active: true
        },
        {
          first_name: 'Juan',
          last_name: 'Dela Cruz',
          middle_name: 'Miguel',
          suffix: 'Jr.',
          position: 'Barangay Councilor',
          photo: null,
          term_start: 2023,
          term_end: 2026,
          is_active: true
        },
        {
          first_name: 'Ana',
          last_name: 'Rodriguez',
          middle_name: 'Luna',
          suffix: '',
          position: 'Barangay Secretary',
          photo: null,
          term_start: 2023,
          term_end: 2026,
          is_active: true
        }
      ];
    }
  } catch (error) {
    // Show sample data on error
    barangayOfficials.value = [
      {
        first_name: 'Maria',
        last_name: 'Santos',
        middle_name: 'Cruz',
        suffix: '',
        position: 'Barangay Captain',
        photo: null,
        term_start: 2023,
        term_end: 2026,
        is_active: true
      },
      {
        first_name: 'Juan',
        last_name: 'Dela Cruz',
        middle_name: 'Miguel',
        suffix: 'Jr.',
        position: 'Barangay Councilor',
        photo: null,
        term_start: 2023,
        term_end: 2026,
        is_active: true
      },
      {
        first_name: 'Ana',
        last_name: 'Rodriguez',
        middle_name: 'Luna',
        suffix: '',
        position: 'Barangay Secretary',
        photo: null,
        term_start: 2023,
        term_end: 2026,
        is_active: true
      }
    ];
  } finally {
    loadingOfficials.value = false;
  }
};

// Fetch Announcements
const fetchAnnouncements = async () => {
  try {
    const response = await fetch('/api/announcements');

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    announcements.value = data;

    // If no announcements found, show sample data for demo
    if (data.length === 0) {
      announcements.value = [
        {
          id: 1,
          title: 'Community Clean-up Drive',
          content: 'Join us for our monthly community clean-up drive this Saturday. All residents are encouraged to participate.',
          type: 'event',
          priority: 'high',
          is_featured: true,
          published_at: new Date().toISOString(),
          author_name: 'Barangay Captain Maria Santos',
          author_position: 'Barangay Captain',
        },
        {
          id: 2,
          title: 'Water Service Interruption Notice',
          content: 'Water service will be temporarily interrupted on Sunday from 6 AM to 12 PM for maintenance work.',
          type: 'notice',
          priority: 'urgent',
          is_featured: false,
          published_at: new Date(Date.now() - 86400000).toISOString(), // 1 day ago
          author_name: 'Public Works Department',
          author_position: 'Public Works',
        },
        {
          id: 3,
          title: 'New Health Center Services',
          content: 'Our health center now offers free vaccination services every Tuesday and Thursday.',
          type: 'general',
          priority: 'normal',
          is_featured: false,
          published_at: new Date(Date.now() - 172800000).toISOString(), // 2 days ago
          author_name: 'Health Center Staff',
          author_position: 'Health Center',
        }
      ];
    }
  } catch (error) {
    // Show sample data on error
    announcements.value = [
      {
        id: 1,
        title: 'Community Clean-up Drive',
        content: 'Join us for our monthly community clean-up drive this Saturday. All residents are encouraged to participate.',
        type: 'event',
        priority: 'high',
        is_featured: true,
        published_at: new Date().toISOString(),
        author_name: 'Barangay Captain Maria Santos',
        author_position: 'Barangay Captain',
      },
      {
        id: 2,
        title: 'Water Service Interruption Notice',
        content: 'Water service will be temporarily interrupted on Sunday from 6 AM to 12 PM for maintenance work.',
        type: 'notice',
        priority: 'urgent',
        is_featured: false,
        published_at: new Date(Date.now() - 86400000).toISOString(),
        author_name: 'Public Works Department',
        author_position: 'Public Works',
      },
      {
        id: 3,
        title: 'New Health Center Services',
        content: 'Our health center now offers free vaccination services every Tuesday and Thursday.',
        type: 'general',
        priority: 'normal',
        is_featured: false,
        published_at: new Date(Date.now() - 172800000).toISOString(),
        author_name: 'Health Center Staff',
        author_position: 'Health Center',
      }
    ];
  } finally {
    loadingAnnouncements.value = false;
  }
};

    // Simple intersection-based reveal animation (no extra libs)
const io = ref<IntersectionObserver | null>(null);
const revealTargets = ref<HTMLElement[]>([]);

onMounted(() => {
  // Show sample data immediately as fallback
  barangayOfficials.value = [
    {
      first_name: 'Maria',
      last_name: 'Santos',
      middle_name: 'Cruz',
      suffix: '',
      position: 'Barangay Captain',
      photo: null,
      term_start: 2023,
      term_end: 2026,
      is_active: true
    },
    {
      first_name: 'Juan',
      last_name: 'Dela Cruz',
      middle_name: 'Miguel',
      suffix: 'Jr.',
      position: 'Barangay Councilor',
      photo: null,
      term_start: 2023,
      term_end: 2026,
      is_active: true
    },
    {
      first_name: 'Ana',
      last_name: 'Rodriguez',
      middle_name: 'Luna',
      suffix: '',
      position: 'Barangay Secretary',
      photo: null,
      term_start: 2023,
      term_end: 2026,
      is_active: true
    },
    {
      first_name: 'Pedro',
      last_name: 'Garcia',
      middle_name: 'Lopez',
      suffix: '',
      position: 'Barangay Treasurer',
      photo: null,
      term_start: 2023,
      term_end: 2026,
      is_active: true
    }
  ];
  loadingOfficials.value = false;

  // Fetch barangay officials (will replace sample data if successful)
  fetchOfficials();
  fetchAnnouncements();

  // Setup intersection observer for animations
  io.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('reveal-show');
          io.value?.unobserve(e.target);
        }
      });
    },
    { threshold: 0.12 }
  );
  // Collect [data-reveal] elements
  revealTargets.value = Array.from(document.querySelectorAll('[data-reveal]')) as HTMLElement[];
  revealTargets.value.forEach((el) => io.value?.observe(el));
});

onBeforeUnmount(() => io.value?.disconnect());

// Utility functions
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Announcement functions
const getAnnouncementPreview = (content: string) => {
  // Create a temporary div to parse HTML
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = content;

  // Remove all elements except images and text
  const elements = tempDiv.querySelectorAll('*');
  elements.forEach(element => {
    if (element.tagName.toLowerCase() !== 'img') {
      // Replace non-image elements with their text content
      const textNode = document.createTextNode(element.textContent || '');
      element.parentNode?.replaceChild(textNode, element);
    }
  });

  // Get text content and limit to 120 characters
  const textContent = tempDiv.textContent || '';
  const preview = textContent.substring(0, 120) + (textContent.length > 120 ? '...' : '');

  // If there are images, add them to the preview
  const images = tempDiv.querySelectorAll('img');
  let result = preview;

  if (images.length > 0) {
    // Add first image to preview
    const firstImage = images[0];
    result = `<div class="mb-2">${firstImage.outerHTML}</div><div class="text-gray-600">${preview}</div>`;
  }

  return result;
};

const openAnnouncementView = (announcement: Announcement) => {
  selectedAnnouncement.value = announcement;
};

const closeAnnouncementView = () => {
  selectedAnnouncement.value = null;
};
</script>

<template>
  <Head title="Uni Respond – Barangay Safety & Incident Management">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <!-- Skip link for a11y -->
  <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:shadow">Skip to content</a>

  <div class="min-h-screen bg-[#F0F9FF] text-[#0f172a]">
    <!-- Top utility bar -->
    <div class="hidden bg-[#0EA5E9] text-white lg:block" aria-label="utility bar">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-2 text-[12px]">
        <div class="flex flex-wrap items-center gap-4 opacity-95">
          <span class="inline-flex items-center gap-1"><Phone class="h-3.5 w-3.5" /> Emergency: 117 • +63 900 000 0000</span>
          <span class="hidden md:inline-flex items-center gap-1"><Mail class="h-3.5 w-3.5" /> purisima@example.com</span>
          <span class="inline-flex items-center gap-1"><MapPin class="h-3.5 w-3.5" /> Barangay Purisima, PH</span>
        </div>
        <div class="flex items-center gap-3">
          <button class="rounded px-2 py-1 text-[12px] ring-offset-2 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/70">A A</button>
          <button class="inline-flex items-center gap-1 rounded px-2 py-1 text-[12px] ring-offset-2 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/70">
            <Globe class="h-3.5 w-3.5" /> EN / FIL / BIS
          </button>
          <Link :href="register().url" class="opacity-95 hover:opacity-100">Register</Link>
          <Link :href="login().url" class="opacity-95 hover:opacity-100">Login</Link>
        </div>
      </div>
    </div>

    <!-- Header / Nav -->
    <header class="sticky top-0 z-40 border-b border-[#E0F2FE] bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75">
      <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6" aria-label="Primary">
        <div class="flex items-center gap-3">
          <img 
            src="/images/logo/unirespond.jpg" 
            alt="Uni Respond Logo" 
            class="h-10 w-10 rounded-lg object-contain animate-float" 
            @error="$event.target.style.display='none'" 
          />
          <div class="leading-tight">
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold tracking-wide text-[#0C4A6E]">Uni Respond</span>
            </div>
            <p class="text-[11px] text-[#155e75] opacity-80">Barangay Safety & Incident Management</p>
          </div>
        </div>

        <!-- Desktop nav -->
        <div class="hidden items-center gap-8 text-[13px] lg:flex">
          <a href="#about" class="rounded px-1 py-1 text-[#075985] hover:text-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">About</a>
          <a href="#services" class="rounded px-1 py-1 text-[#075985] hover:text-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">Services</a>
          <a href="#pillars" class="rounded px-1 py-1 text-[#075985] hover:text-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">Pillars</a>
          <a href="#faq" class="rounded px-1 py-1 text-[#075985] hover:text-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">FAQ</a>
          <a href="#contact" class="rounded px-1 py-1 text-[#075985] hover:text-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">Contact</a>
        </div>

        <div class="hidden lg:block">
          <Link :href="isAuthed ? dashboard().url : login().url" class="group relative inline-flex items-center gap-2 overflow-hidden rounded-md bg-[#0EA5E9] px-4 py-2 text-[12px] font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">
            <Sparkles class="h-4 w-4 transition-transform group-hover:scale-110" />
            {{ isAuthed ? 'Go to Dashboard' : 'Report Incident' }}
            <span class="pointer-events-none absolute inset-0 translate-y-full bg-white/15 transition-transform duration-500 group-hover:translate-y-0" />
          </Link>
        </div>

        <!-- Mobile trigger -->
        <button @click="toggleMobile" class="rounded p-2 text-[#075985] lg:hidden focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]" :aria-expanded="mobileOpen" aria-controls="mobile-nav">
          <span class="sr-only">Open menu</span>
          <component :is="mobileOpen ? X : Menu" class="h-6 w-6" />
        </button>
      </nav>

      <!-- Mobile panel -->
      <transition name="slide-fade">
        <div v-if="mobileOpen" id="mobile-nav" class="border-t border-[#E0F2FE] bg-white lg:hidden">
          <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 text-[14px] sm:px-6">
            <a href="#about" class="block rounded px-2 py-2 hover:bg-[#F0F9FF]">About</a>
            <a href="#services" class="block rounded px-2 py-2 hover:bg-[#F0F9FF]">Services</a>
            <a href="#pillars" class="block rounded px-2 py-2 hover:bg-[#F0F9FF]">Pillars</a>
            <a href="#faq" class="block rounded px-2 py-2 hover:bg-[#F0F9FF]">FAQ</a>
            <a href="#contact" class="block rounded px-2 py-2 hover:bg-[#F0F9FF]">Contact</a>
            <Link :href="isAuthed ? dashboard().url : login().url" class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-[#0EA5E9] px-4 py-2 font-semibold text-white">{{ isAuthed ? 'Go to Dashboard' : 'Report Incident' }}</Link>
          </div>
        </div>
      </transition>
    </header>

    <main id="main">
      <!-- HERO -->
      <section class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-blue-50 to-indigo-100"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-[#0284C7]/80 via-[#0EA5E9]/70 to-[#38BDF8]/80"></div>

        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-6 py-16 sm:py-20 md:grid-cols-2">
          <div data-reveal class="reveal">
            <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-white md:text-5xl">
              Unified Barangay Safety Platform
            </h1>
            <p class="mb-8 max-w-xl text-[14px] text-white/90 md:text-[15px]">
              Coordinate responders, inform the community, and manage incidents across web and mobile using a secure, data‑driven workflow.
            </p>
            <div class="flex flex-wrap items-center gap-3">
              <Link :href="isAuthed ? dashboard().url : login().url" class="group relative inline-flex items-center gap-2 overflow-hidden rounded-md bg-white px-5 py-2 text-sm font-semibold text-[#075985] shadow transition focus:outline-none focus:ring-2 focus:ring-white/70">
                <Send class="h-4 w-4" /> Start now
                <span class="pointer-events-none absolute inset-0 translate-x-[-120%] bg-[#7dd3fc]/40 transition-transform duration-500 group-hover:translate-x-[0%]" />
              </Link>
              <a href="#pillars" class="rounded-md border border-white/40 px-5 py-2 text-sm text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60">Learn more</a>
            </div>

            <!-- Trust strip -->
            <div class="mt-10 grid max-w-xl grid-cols-2 gap-3 rounded-xl bg-white/10 p-4 text-left text-[13px] backdrop-blur sm:grid-cols-3">
              <div class="rounded-lg bg-white/5 p-3 text-white">
                <div class="text-xl font-bold">24/7</div>
                <div class="opacity-90">Monitoring</div>
              </div>
              <div class="rounded-lg bg-white/5 p-3 text-white">
                <div class="text-xl font-bold">Role‑based</div>
                <div class="opacity-90">access control</div>
              </div>
              <div class="rounded-lg bg-white/5 p-3 text-white">
                <div class="text-xl font-bold">LGU</div>
                <div class="opacity-90">ready workflows</div>
              </div>
            </div>
          </div>

          <!-- Right visual card -->
          <div data-reveal class="reveal">
            <div class="relative overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-black/5">
              <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-[#E0F2FE] blur-3xl"></div>
              <div class="relative space-y-4 p-6">
                <div class="flex items-center gap-2 text-[#0369a1]"><Shield class="h-4 w-4" /><span class="text-sm font-semibold">Secure Incident Intake</span></div>
                <div class="grid grid-cols-2 gap-3 text-[12px] text-[#334155]">
                  <div class="rounded-lg border border-[#E2E8F0] p-3">
                    <div class="mb-1 flex items-center gap-2 text-[#0EA5E9]"><AlertTriangle class="h-4 w-4" /> Report</div>
                    Attach photos/videos and location for quick triage.
                  </div>
                  <div class="rounded-lg border border-[#E2E8F0] p-3">
                    <div class="mb-1 flex items-center gap-2 text-[#0EA5E9]"><FileText class="h-4 w-4" /> Triage</div>
                    Staff verify, categorize, and prioritize.
                  </div>
                  <div class="rounded-lg border border-[#E2E8F0] p-3">
                    <div class="mb-1 flex items-center gap-2 text-[#0EA5E9]"><Send class="h-4 w-4" /> Dispatch</div>
                    Enforcers receive tasks and coordinate.
                  </div>
                  <div class="rounded-lg border border-[#E2E8F0] p-3">
                    <div class="mb-1 flex items-center gap-2 text-[#0EA5E9]"><ClipboardCheck class="h-4 w-4" /> Resolve</div>
                    Completion with evidence and feedback.
                  </div>
                </div>
                <div class="flex items-center gap-2 rounded-lg bg-[#F0F9FF] p-3 text-[12px] text-[#075985]"><Lock class="h-4 w-4" /> Aligned to good‑practice flows; no certification claimed.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Primary Features: Calamity Reports & Location Tracking -->
      <section class="relative px-6 py-20 overflow-hidden bg-gradient-to-br from-red-50 via-orange-50 to-amber-50">
        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
          <div class="absolute top-10 left-10 w-96 h-96 bg-red-200/20 rounded-full blur-3xl animate-pulse"></div>
          <div class="absolute bottom-10 right-10 w-96 h-96 bg-orange-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative mx-auto max-w-7xl">
          <!-- Section Header -->
          <div class="text-center mb-16">
            <div class="inline-flex items-center justify-center gap-3 mb-4">
              <div class="p-3 bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-lg">
                <AlertTriangle class="h-8 w-8 text-white" />
              </div>
              <h2 class="text-4xl md:text-5xl font-bold text-gray-900">Primary Features</h2>
            </div>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
              Real-time calamity reporting and location tracking for faster emergency response
            </p>
          </div>

          <!-- Feature Cards -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Calamity Reports Feature -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 md:p-10 shadow-2xl border-2 border-red-100 hover:border-red-300 transition-all duration-500 transform hover:-translate-y-2 overflow-hidden">
              <!-- Background decoration -->
              <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-red-100/30 to-orange-100/30 rounded-full -translate-y-32 translate-x-32"></div>
              
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-5 bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <AlertTriangle class="h-10 w-10 text-white" />
                  </div>
                  <div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">Calamity Reports</h3>
                    <p class="text-sm text-gray-500">Emergency incident reporting system</p>
                  </div>
                </div>
                
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                  Report emergencies instantly with automatic location sharing, detailed needs assessment, and real-time status tracking.
                </p>

                <div class="space-y-4 mb-6">
                  <div class="flex items-start gap-3 p-4 bg-red-50 rounded-xl border border-red-100">
                    <MapPin class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Automatic Location Sharing</div>
                      <p class="text-sm text-gray-600">Your location is automatically captured and shared with responders</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-orange-50 rounded-xl border border-orange-100">
                    <Heart class="h-5 w-5 text-orange-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Needs Assessment</div>
                      <p class="text-sm text-gray-600">Specify your needs: food, water, medicine, shelter, and more</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <Users class="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Vulnerable Groups</div>
                      <p class="text-sm text-gray-600">Indicate if you have elderly, children, PWD, or pregnant individuals</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-red-50 rounded-xl border border-red-100">
                    <Clock class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Real-time Status Updates</div>
                      <p class="text-sm text-gray-600">Track your report status from pending to resolved</p>
                    </div>
                  </div>
                </div>

                <Link 
                  :href="isAuthed ? '/resident/calamity' : register().url" 
                  class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-orange-600 text-white font-semibold rounded-xl hover:shadow-xl transition-all duration-300 group"
                >
                  <AlertTriangle class="h-5 w-5 group-hover:animate-pulse" />
                  {{ isAuthed ? 'View Reports' : 'Get Started' }}
                  <Send class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </div>

            <!-- Location Tracking Feature -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 md:p-10 shadow-2xl border-2 border-blue-100 hover:border-blue-300 transition-all duration-500 transform hover:-translate-y-2 overflow-hidden">
              <!-- Background decoration -->
              <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-100/30 to-cyan-100/30 rounded-full -translate-y-32 translate-x-32"></div>
              
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-5 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <Navigation class="h-10 w-10 text-white" />
                  </div>
                  <div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">Location Tracking</h3>
                    <p class="text-sm text-gray-500">Real-time location sharing & monitoring</p>
                  </div>
                </div>
                
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">
                  See staff and residents on an interactive map. Share your location in real-time for coordinated emergency response.
                </p>

                <div class="space-y-4 mb-6">
                  <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <div class="h-5 w-5 rounded-full bg-green-500 border-2 border-white shadow-sm mt-0.5 flex-shrink-0"></div>
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Staff Locations (Green)</div>
                      <p class="text-sm text-gray-600">View all staff members on the map for quick coordination</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-cyan-50 rounded-xl border border-cyan-100">
                    <div class="h-5 w-5 rounded-full bg-blue-500 border-2 border-white shadow-sm mt-0.5 flex-shrink-0"></div>
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Resident Locations (Blue)</div>
                      <p class="text-sm text-gray-600">See where other residents are located during emergencies</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                    <Radio class="h-5 w-5 text-indigo-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Real-time Updates</div>
                      <p class="text-sm text-gray-600">Locations update automatically every 5 seconds</p>
                    </div>
                  </div>
                  
                  <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <Shield class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 mb-1">Privacy Controls</div>
                      <p class="text-sm text-gray-600">Control when and with whom you share your location</p>
                    </div>
                  </div>
                </div>

                <Link 
                  :href="isAuthed ? '/resident/calamity/map' : register().url" 
                  class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-semibold rounded-xl hover:shadow-xl transition-all duration-300 group"
                >
                  <Navigation class="h-5 w-5 group-hover:animate-spin" />
                  {{ isAuthed ? 'View Map' : 'Get Started' }}
                  <Send class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </div>
          </div>

          <!-- How It Works -->
          <div class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 md:p-12 shadow-xl border border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">How It Works</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-red-500 to-orange-500 text-white text-2xl font-bold mb-4 shadow-lg">1</div>
                <h4 class="font-semibold text-gray-900 mb-2">Report Emergency</h4>
                <p class="text-sm text-gray-600">Tap the emergency button and share your location automatically</p>
              </div>
              <div class="text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 text-white text-2xl font-bold mb-4 shadow-lg">2</div>
                <h4 class="font-semibold text-gray-900 mb-2">Specify Needs</h4>
                <p class="text-sm text-gray-600">Tell us what you need: food, water, medicine, shelter, etc.</p>
              </div>
              <div class="text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-2xl font-bold mb-4 shadow-lg">3</div>
                <h4 class="font-semibold text-gray-900 mb-2">Track on Map</h4>
                <p class="text-sm text-gray-600">Staff can see your location and dispatch help immediately</p>
              </div>
              <div class="text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 text-white text-2xl font-bold mb-4 shadow-lg">4</div>
                <h4 class="font-semibold text-gray-900 mb-2">Get Assistance</h4>
                <p class="text-sm text-gray-600">Receive help from staff who can track your location in real-time</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Core Pillars -->
      <section id="pillars" class="mt-12 grid grid-cols-1 gap-6 bg-white px-6 py-12 shadow-[0_1px_0_rgba(2,132,199,0.12),inset_0_0_0_1px_rgba(14,165,233,0.10)] lg:grid-cols-3 lg:px-16">
        <div class="col-span-1 lg:col-span-2">
          <h2 class="mb-4 text-lg font-semibold text-[#0C4A6E]">Core pillars</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-4">
              <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#E0F2FE] text-[#0284C7]"><Send class="h-4 w-4" /></div>
              <h3 class="mb-1 font-medium">Unified Response</h3>
              <p class="text-[#475569]">Synchronize officials, responders, and residents for coordinated actions across web and mobile.</p>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-4">
              <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#DBEAFE] text-[#2563EB]"><FileText class="h-4 w-4" /></div>
              <h3 class="mb-1 font-medium">Information Management</h3>
              <p class="text-[#475569]">Real‑time, accurate data streams enable informed decisions and instant updates.</p>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-4">
              <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#CFFAFE] text-[#06B6D4]"><Bell class="h-4 w-4" /></div>
              <h3 class="mb-1 font-medium">Safety</h3>
              <p class="text-[#475569]">Preventive tools, early hazard detection, and timely push notifications.</p>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-4">
              <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#D1FAE5] text-[#16A34A]"><Activity class="h-4 w-4" /></div>
              <h3 class="mb-1 font-medium">Incident Management</h3>
              <p class="text-[#475569]">Track, prioritize, and resolve incidents end‑to‑end with transparency.</p>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-4 sm:col-span-2">
              <div class="mb-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#FEE2E2] text-[#EF4444]"><Users class="h-4 w-4" /></div>
              <h3 class="mb-1 font-medium">Awareness</h3>
              <p class="text-[#475569]">Engage the community with educational campaigns and regular safety updates.</p>
            </div>
          </div>
        </div>

        <div class="col-span-1">
          <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] p-5">
            <h3 class="mb-1 font-medium text-[#0C4A6E]">Web + Mobile</h3>
            <p class="mb-3 text-[#475569]">Residents submit incidents with media; officials coordinate and resolve in real time.</p>
            <ul class="space-y-1 text-[#0f172a]">
              <li class="flex items-center gap-2"><Smartphone class="h-4 w-4 text-[#0284C7]" /> Real‑time notifications</li>
              <li class="flex items-center gap-2"><Shield class="h-4 w-4 text-[#2563EB]" /> Role‑based portals</li>
              <li class="flex items-center gap-2"><Activity class="h-4 w-4 text-[#16A34A]" /> Data‑driven dashboards</li>
            </ul>
            <div class="mt-4 grid grid-cols-2 gap-2 text-[12px]">
              <a href="#" class="inline-flex items-center justify-center rounded border border-[#E2E8F0] bg-white px-3 py-2">Get Android App</a>
              <a href="#" class="inline-flex items-center justify-center rounded border border-[#E2E8F0] bg-white px-3 py-2">Get iOS App</a>
            </div>
          </div>
        </div>
      </section>

      <!-- Barangay Officials Showcase -->
      <section class="relative bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 px-6 py-20 overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden">
          <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-200/30 to-purple-200/30 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-indigo-200/30 to-pink-200/30 rounded-full blur-3xl"></div>
        </div>

        <!-- Announcements Section -->
        <div class="relative mx-auto max-w-7xl mb-24">
          <div class="text-center mb-16">
            <div class="inline-flex items-center gap-4 mb-6">
              <div class="p-4 bg-gradient-to-r from-green-500 to-teal-500 rounded-2xl shadow-lg">
                <Bell class="h-10 w-10 text-white" />
              </div>
              <div class="text-left">
                <h2 class="text-4xl font-bold text-gray-900 mb-2">Latest Announcements</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-teal-500 rounded-full"></div>
              </div>
            </div>
            <p class="text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
              Stay informed with the latest news, events, and important notices from our barangay administration.
            </p>
          </div>

          <!-- Announcements Grid -->
          <div v-if="announcements.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div
              v-for="(announcement, index) in announcements"
              :key="announcement.id"
              class="group relative bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 transform hover:-translate-y-2"
              :class="{ 'lg:col-span-2': announcement.is_featured && index === 0 }"
            >
              <!-- Priority Indicator -->
              <div
                class="absolute top-4 right-4 z-10"
                :class="{
                  'bg-red-500': announcement.priority === 'urgent',
                  'bg-orange-500': announcement.priority === 'high',
                  'bg-blue-500': announcement.priority === 'normal',
                  'bg-gray-500': announcement.priority === 'low'
                }"
              >
                <div class="px-3 py-1 text-white text-xs font-semibold rounded-full">
                  {{ announcement.priority.toUpperCase() }}
                </div>
              </div>

              <!-- Featured Badge -->
              <div v-if="announcement.is_featured" class="absolute top-4 left-4 z-10">
                <div class="px-3 py-1 bg-purple-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
                  <Star class="w-3 h-3" />
                  FEATURED
                </div>
              </div>

              <!-- Image -->
              <div v-if="announcement.image_path" class="relative h-48 overflow-hidden">
                <img
                  :src="`/storage/${announcement.image_path}`"
                  :alt="announcement.title"
                  class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
              </div>

              <!-- Content -->
              <div class="p-8">
                <!-- Type Badge -->
                <div class="mb-4">
                  <span
                    class="px-3 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-red-100 text-red-800': announcement.type === 'urgent',
                      'bg-green-100 text-green-800': announcement.type === 'event',
                      'bg-blue-100 text-blue-800': announcement.type === 'notice',
                      'bg-gray-100 text-gray-800': announcement.type === 'general'
                    }"
                  >
                    {{ announcement.type.toUpperCase() }}
                  </span>
                </div>

                <!-- Title -->
                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                  {{ announcement.title }}
                </h3>

                <!-- Content Preview -->
                <div class="text-gray-600 mb-4 line-clamp-3 prose prose-sm max-w-none" v-html="getAnnouncementPreview(announcement.content)"></div>

                <!-- Author & Date -->
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                  <div v-if="announcement.author_name" class="flex items-center gap-2">
                    <User class="w-4 h-4" />
                    <span>{{ announcement.author_name }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Calendar class="w-4 h-4" />
                    <span>{{ formatDate(announcement.published_at) }}</span>
                  </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end">
                  <button
                    @click="openAnnouncementView(announcement)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-500 to-teal-500 text-white rounded-lg hover:from-green-600 hover:to-teal-600 transition-all duration-300 transform hover:scale-105"
                  >
                    <span class="text-sm font-medium">Read More</span>
                    <ArrowRight class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Hover Effect Overlay -->
              <div class="absolute inset-0 bg-gradient-to-r from-green-500/5 to-teal-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-else-if="loadingAnnouncements" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="i in 3" :key="i" class="bg-white rounded-3xl shadow-xl overflow-hidden">
              <div class="h-48 bg-gray-200 animate-pulse"></div>
              <div class="p-8">
                <div class="h-4 bg-gray-200 rounded animate-pulse mb-4"></div>
                <div class="h-6 bg-gray-200 rounded animate-pulse mb-3"></div>
                <div class="h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                <div class="h-4 bg-gray-200 rounded animate-pulse mb-4"></div>
                <div class="h-8 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-16">
            <Bell class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No announcements yet</h3>
            <p class="text-gray-600">Check back later for important updates and news.</p>
          </div>

          <!-- View All Button -->
          <div class="text-center mt-12">
            <Link
              v-if="isAuthed"
              :href="route('staff.announcements.index')"
              class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-green-500 to-teal-500 text-white rounded-xl hover:from-green-600 hover:to-teal-600 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
            >
              <span class="font-semibold">View All Announcements</span>
              <ArrowRight class="w-5 h-5" />
            </Link>
          </div>
        </div>

        <div class="relative mx-auto max-w-7xl">
          <div class="text-center mb-16">
            <div class="inline-flex items-center gap-4 mb-6">
              <div class="p-4 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl shadow-lg">
                <Users class="h-10 w-10 text-white" />
              </div>
              <div class="text-left">
                <h2 class="text-4xl font-bold text-gray-900 mb-2">Meet Our Barangay Officials</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"></div>
              </div>
            </div>
            <p class="text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
              Dedicated public servants working tirelessly to serve our community with integrity, transparency, and commitment to excellence.
            </p>
          </div>

          <!-- Officials Grid -->
          <div v-if="barangayOfficials.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div
              v-for="(official, index) in barangayOfficials"
              :key="`${official.first_name}-${official.last_name}`"
              class="group relative bg-white rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 transform hover:-translate-y-2 opacity-100"
            >
              <!-- Gradient overlay on hover -->
              <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 to-purple-500/0 group-hover:from-blue-500/10 group-hover:to-purple-500/10 transition-all duration-500"></div>

              <!-- Decorative elements -->
              <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-100/50 to-purple-100/50 rounded-full -translate-y-16 translate-x-16"></div>

              <div class="relative p-8">
                <!-- Photo -->
                <div class="flex justify-center mb-6">
                  <div class="relative">
                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 ring-4 ring-white shadow-xl group-hover:ring-blue-200 transition-all duration-300">
                      <img
                        v-if="official.photo"
                        :src="`/storage/${official.photo}`"
                        :alt="`${official.first_name} ${official.last_name}`"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                      />
                      <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-200 to-purple-200">
                        <User class="h-12 w-12 text-blue-600" />
                      </div>
                    </div>
                    <!-- Status indicator -->
                    <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full border-4 border-white bg-green-500 flex items-center justify-center shadow-lg">
                      <div class="w-3 h-3 rounded-full bg-white"></div>
                    </div>
                  </div>
                </div>

                <!-- Name and Position -->
                <div class="text-center mb-6">
                  <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-300">
                    {{ official.first_name }} {{ official.last_name }}
                    <span v-if="official.middle_name" class="text-gray-600">{{ official.middle_name }}</span>
                    <span v-if="official.suffix" class="text-gray-600">{{ official.suffix }}</span>
                  </h3>
                  <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white text-sm font-bold rounded-full shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <Award class="h-4 w-4" />
                    {{ official.position }}
                  </div>
                </div>

                <!-- Term Information -->
                <div v-if="official.term_start || official.term_end" class="flex items-center justify-center gap-2 text-sm text-gray-600 mb-6 p-3 bg-gray-50 rounded-xl">
                  <Calendar class="h-4 w-4 text-blue-500" />
                  <span class="font-medium">
                    Term: {{ official.term_start || 'N/A' }}
                    <span v-if="official.term_start && official.term_end"> - </span>
                    {{ official.term_end || 'Present' }}
                  </span>
                </div>

                <!-- Contact Actions -->
                <div class="flex gap-3">
                  <button class="flex-1 flex items-center justify-center gap-2 py-3 px-4 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 text-blue-600 rounded-xl transition-all duration-300 text-sm font-semibold shadow-sm hover:shadow-md">
                    <Eye class="h-4 w-4" />
                    View Profile
                  </button>
                  <button class="flex-1 flex items-center justify-center gap-2 py-3 px-4 bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 text-green-600 rounded-xl transition-all duration-300 text-sm font-semibold shadow-sm hover:shadow-md">
                    <Phone class="h-4 w-4" />
                    Contact
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-else-if="loadingOfficials" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="i in 6" :key="i" class="bg-white rounded-2xl shadow-lg p-6 animate-pulse">
              <div class="flex justify-center mb-4">
                <div class="w-20 h-20 rounded-full bg-gray-200"></div>
              </div>
              <div class="text-center mb-4">
                <div class="h-6 bg-gray-200 rounded mb-2"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4 mx-auto"></div>
              </div>
              <div class="h-4 bg-gray-200 rounded mb-4"></div>
              <div class="flex gap-2">
                <div class="flex-1 h-8 bg-gray-200 rounded"></div>
                <div class="flex-1 h-8 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
              <Users class="h-12 w-12 text-gray-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Officials Available</h3>
            <p class="text-gray-600">Barangay officials information will be displayed here once available.</p>
          </div>

          <!-- View All Button -->
          <div v-if="barangayOfficials.length > 0" class="text-center mt-8">
            <Link :href="isAuthed ? dashboard().url : login().url" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white font-semibold rounded-lg hover:shadow-lg transition-all duration-300">
              <Users class="h-5 w-5" />
              View All Officials
              <Sparkles class="h-4 w-4" />
            </Link>
          </div>
        </div>
      </section>

      <!-- Platform Features Overview -->
      <section class="relative bg-gradient-to-br from-gray-50 via-white to-blue-50 px-6 py-20 overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden">
          <div class="absolute top-20 left-20 w-64 h-64 bg-gradient-to-br from-green-200/20 to-teal-200/20 rounded-full blur-3xl"></div>
          <div class="absolute bottom-20 right-20 w-64 h-64 bg-gradient-to-br from-blue-200/20 to-indigo-200/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl">
          <div class="text-center mb-16">
            <div class="inline-flex items-center gap-4 mb-6">
              <div class="p-4 bg-gradient-to-r from-green-500 to-teal-500 rounded-2xl shadow-lg">
                <Building2 class="h-10 w-10 text-white" />
              </div>
              <div class="text-left">
                <h2 class="text-4xl font-bold text-gray-900 mb-2">Comprehensive Platform Features</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-teal-500 rounded-full"></div>
              </div>
            </div>
            <p class="text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
              Our integrated platform provides everything needed for modern barangay governance, from document management to incident response.
            </p>
          </div>

          <!-- Features Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Document Management -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <!-- Background decoration -->
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-100/50 to-indigo-100/50 rounded-full -translate-y-12 translate-x-12"></div>

              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <FileText class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300">Document Management</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Streamlined document processing for certificates, clearances, and official records.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Online document requests</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Digital document templates</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Automated approval workflows</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Secure document storage</span></li>
                </ul>
              </div>
            </div>

            <!-- Incident Management -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-red-100/50 to-orange-100/50 rounded-full -translate-y-12 translate-x-12"></div>
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <AlertTriangle class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-red-600 transition-colors duration-300">Incident Management</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Comprehensive incident reporting, tracking, and resolution system.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Real-time incident reporting</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">GPS location tracking</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Photo/video evidence</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Status tracking & updates</span></li>
                </ul>
              </div>
            </div>

            <!-- Community Engagement -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100/50 to-emerald-100/50 rounded-full -translate-y-12 translate-x-12"></div>
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <Heart class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-green-600 transition-colors duration-300">Community Engagement</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Foster stronger community connections through transparent communication.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Public announcements</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Community feedback system</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Event notifications</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Transparency reports</span></li>
                </ul>
              </div>
            </div>

            <!-- Staff Management -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-100/50 to-pink-100/50 rounded-full -translate-y-12 translate-x-12"></div>
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <Users class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors duration-300">Staff Management</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Efficient management of barangay officials and staff members.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Official profiles & bios</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Term management</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Contact information</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Role-based access</span></li>
                </ul>
              </div>
            </div>

            <!-- Analytics & Reporting -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-100/50 to-blue-100/50 rounded-full -translate-y-12 translate-x-12"></div>
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <Database class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-300">Analytics & Reporting</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Data-driven insights for better decision making and transparency.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Performance dashboards</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Incident statistics</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Document processing metrics</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Public reports</span></li>
                </ul>
              </div>
            </div>

            <!-- Mobile Accessibility -->
            <div data-reveal class="reveal group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100 transform hover:-translate-y-2 overflow-hidden">
              <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-teal-100/50 to-cyan-100/50 rounded-full -translate-y-12 translate-x-12"></div>
              <div class="relative">
                <div class="flex items-center gap-4 mb-6">
                  <div class="p-4 bg-gradient-to-r from-teal-500 to-cyan-500 rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300">
                    <Smartphone class="h-8 w-8 text-white" />
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 group-hover:text-teal-600 transition-colors duration-300">Mobile Accessibility</h3>
                </div>
                <p class="text-gray-600 mb-6 text-lg leading-relaxed">Access all features seamlessly across desktop and mobile devices.</p>
                <ul class="space-y-3 text-gray-600">
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Responsive design</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Mobile-optimized forms</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Push notifications</span></li>
                  <li class="flex items-center gap-3"><CheckCircle class="h-5 w-5 text-green-500 flex-shrink-0" /> <span class="font-medium">Offline capabilities</span></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Services -->
      <section id="services" class="bg-[#EFF6FF] px-6 py-12">
        <div class="mx-auto max-w-7xl">
          <div class="mb-6 flex items-center gap-2">
            <CheckCircle2 class="h-5 w-5 text-[#0284C7]" />
            <h2 class="text-lg font-semibold text-[#0C4A6E]">Online Services</h2>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] bg-white p-5">
              <h3 class="mb-1 font-medium">Report an Incident</h3>
              <p class="mb-3 text-[13px] text-[#475569]">Submit photos/videos, location, and description. Receive tracking ID.</p>
              <Link :href="isAuthed ? dashboard().url : login().url" class="inline-flex items-center gap-2 rounded-md bg-[#0EA5E9] px-3 py-2 text-sm font-semibold text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-[#7dd3fc]">
                Start
              </Link>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] bg-white p-5">
              <h3 class="mb-1 font-medium">Clearance & Certifications</h3>
              <p class="mb-3 text-[13px] text-[#475569]">Request barangay clearance, residency, and related documents.</p>
              <a href="#" class="inline-flex items-center gap-2 rounded-md bg-[#10B981] px-3 py-2 text-sm font-semibold text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-[#a7f3d0]">Apply</a>
            </div>
            <div data-reveal class="reveal rounded-lg border border-[#E2E8F0] bg-white p-5">
              <h3 class="mb-1 font-medium">Community Alerts</h3>
              <p class="mb-3 text-[13px] text-[#475569]">Opt‑in to SMS/push alerts for advisories, weather, and road status.</p>
              <a href="#" class="inline-flex items-center gap-2 rounded-md bg-[#F59E0B] px-3 py-2 text-sm font-semibold text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-[#fde68a]">Subscribe</a>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section id="faq" class="bg-white px-6 py-12">
        <div class="mx-auto max-w-4xl">
          <div class="mb-6 flex items-center gap-2">
            <Info class="h-5 w-5 text-[#2563EB]" />
            <h2 class="text-lg font-semibold text-[#0C4A6E]">Frequently Asked Questions</h2>
          </div>
          <div class="divide-y divide-[#E2E8F0]">
            <details class="group py-4">
              <summary class="flex cursor-pointer list-none items-center justify-between text-[14px] font-medium text-[#0f172a] hover:text-[#0EA5E9]">How do I report an emergency?
                <span class="ml-4 text-sm text-[#64748b] group-open:hidden">Open</span>
                <span class="ml-4 hidden text-sm text-[#64748b] group-open:inline">Close</span>
              </summary>
              <p class="mt-2 text-[13px] text-[#475569]">Dial 117 or the barangay hotline, then file a report here for documentation and follow‑through.</p>
            </details>
            <details class="group py-4">
              <summary class="flex cursor-pointer list-none items-center justify-between text-[14px] font-medium text-[#0f172a] hover:text-[#0EA5E9]">What happens after I submit an incident?
                <span class="ml-4 text-sm text-[#64748b] group-open:hidden">Open</span>
                <span class="ml-4 hidden text-sm text-[#64748b] group-open:inline">Close</span>
              </summary>
              <p class="mt-2 text-[13px] text-[#475569]">Your case is triaged by staff, dispatched to responders, and tracked until resolution. You will receive updates.</p>
            </details>
            <details class="group py-4">
              <summary class="flex cursor-pointer list-none items-center justify-between text-[14px] font-medium text-[#0f172a] hover:text-[#0EA5E9]">Is my data protected?
                <span class="ml-4 text-sm text-[#64748b] group-open:hidden">Open</span>
                <span class="ml-4 hidden text-sm text-[#64748b] group-open:inline">Close</span>
              </summary>
              <p class="mt-2 text-[13px] text-[#475569]">Yes. Access is role‑based and activity is logged. See Privacy and Data Policy in the footer.</p>
            </details>
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer id="contact" class="border-t border-[#E0F2FE] bg-[#F0F9FF]">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-6 py-10 md:grid-cols-4">
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <img 
                src="/images/logo/unirespond.jpg" 
                alt="Uni Respond Logo" 
                class="h-8 w-8 rounded-lg object-contain" 
                @error="$event.target.style.display='none'" 
              />
              <span class="text-sm font-semibold tracking-wide text-[#0C4A6E]">Uni Respond</span>
            </div>
            <p class="text-[12px] text-[#0C4A6E]/80">Barangay Safety & Incident Management</p>
            <div class="mt-3 space-y-1 text-[13px] text-[#0f172a]">
              <div class="flex items-center gap-2"><MapPin class="h-4 w-4 text-[#0284C7]" /> Barangay Purisima, Philippines</div>
              <div class="flex items-center gap-2"><Phone class="h-4 w-4 text-[#0284C7]" /> +63 900 000 0000</div>
              <div class="flex items-center gap-2"><Mail class="h-4 w-4 text-[#0284C7]" /> purisima@example.com</div>
              <div class="flex items-center gap-2"><Accessibility class="h-4 w-4 text-[#0284C7]" /> Accessibility options available</div>
            </div>
          </div>

          <div>
            <h3 class="mb-2 text-sm font-semibold text-[#0C4A6E]">Citizen Charter</h3>
            <ul class="space-y-1 text-[13px] text-[#0f172a]">
              <li><a href="#services" class="hover:text-[#0EA5E9]">Online Services</a></li>
              <li><a href="#faq" class="hover:text-[#0EA5E9]">FAQ</a></li>
              <li><a href="#" class="hover:text-[#0EA5E9]">Feedback & Complaints</a></li>
              <li><a href="#" class="hover:text-[#0EA5E9]">Transparency</a></li>
            </ul>
          </div>
          <div>
            <h3 class="mb-2 text-sm font-semibold text-[#0C4A6E]">Policies</h3>
            <ul class="space-y-1 text-[13px] text-[#0f172a]">
              <li class="inline-flex items-center gap-2"><Lock class="h-4 w-4 text-[#0284C7]" /><a href="#" class="hover:text-[#0EA5E9]">Privacy & Data Policy</a></li>
              <li><a href="#" class="hover:text-[#0EA5E9]">Terms of Use</a></li>
              <li><a href="#" class="hover:text-[#0EA5E9]">FOI</a></li>
              <li><a href="#" class="hover:text-[#0EA5E9]">Security & Responsible Disclosure</a></li>
            </ul>
          </div>
          <div>
            <h3 class="mb-2 text-sm font-semibold text-[#0C4A6E]">Quick Links</h3>
            <ul class="space-y-1 text-[13px] text-[#0f172a]">
              <li><a href="#about" class="hover:text-[#0EA5E9]">About the Barangay</a></li>
              <li><a href="#pillars" class="hover:text-[#0EA5E9]">Program Pillars</a></li>
              <li><a href="#services" class="hover:text-[#0EA5E9]">Request a Document</a></li>
              <li><a href="#contact" class="hover:text-[#0EA5E9]">Contact Us</a></li>
            </ul>
          </div>
        </div>
        <div class="border-t border-[#E0F2FE] bg-white">
          <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 py-4 text-center text-[12px] text-[#0f172a] sm:flex-row">
            <span>© {{ new Date().getFullYear() }} Uni Respond. All rights reserved.</span>
            <span>Built for public service • v1.0</span>
          </div>
        </div>
      </footer>
    </main>

    <!-- Announcement View Modal -->
    <div v-if="selectedAnnouncement" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeAnnouncementView"></div>

      <!-- Modal Content -->
      <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
          <!-- Header -->
          <div class="relative bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 px-8 py-6">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                  <Bell class="w-6 h-6 text-white" />
                </div>
                <div>
                  <h2 class="text-2xl font-bold text-white">Announcement Details</h2>
                  <p class="text-blue-100">Important information from barangay administration</p>
                </div>
              </div>
              <button
                @click="closeAnnouncementView"
                class="p-2 bg-white/20 rounded-lg backdrop-blur-sm hover:bg-white/30 transition-colors"
              >
                <X class="w-6 h-6 text-white" />
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="p-8 overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Priority & Type Badges -->
            <div class="flex gap-3 mb-6">
              <div
                class="px-4 py-2 text-sm font-semibold rounded-full text-white"
                :class="{
                  'bg-red-500': selectedAnnouncement.priority === 'urgent',
                  'bg-orange-500': selectedAnnouncement.priority === 'high',
                  'bg-blue-500': selectedAnnouncement.priority === 'normal',
                  'bg-gray-500': selectedAnnouncement.priority === 'low'
                }"
              >
                {{ selectedAnnouncement.priority.toUpperCase() }} PRIORITY
              </div>
              <div
                class="px-4 py-2 text-sm font-semibold rounded-full"
                :class="{
                  'bg-red-100 text-red-800': selectedAnnouncement.type === 'urgent',
                  'bg-green-100 text-green-800': selectedAnnouncement.type === 'event',
                  'bg-blue-100 text-blue-800': selectedAnnouncement.type === 'notice',
                  'bg-gray-100 text-gray-800': selectedAnnouncement.type === 'general'
                }"
              >
                {{ selectedAnnouncement.type.toUpperCase() }}
              </div>
              <div v-if="selectedAnnouncement.is_featured" class="px-4 py-2 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full flex items-center gap-1">
                <Star class="w-3 h-3" />
                FEATURED
              </div>
            </div>

            <!-- Title -->
            <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ selectedAnnouncement.title }}</h1>

            <!-- Image -->
            <div v-if="selectedAnnouncement.image_path" class="mb-8">
              <img
                :src="`/storage/${selectedAnnouncement.image_path}`"
                :alt="selectedAnnouncement.title"
                class="w-full h-64 object-cover rounded-xl shadow-lg"
              />
            </div>

            <!-- Content -->
            <div class="prose prose-lg max-w-none mb-8" v-html="selectedAnnouncement.content"></div>

            <!-- Author & Date -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
              <div v-if="selectedAnnouncement.author_name" class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg">
                  <User class="w-5 h-5 text-gray-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500">Author</p>
                  <p class="font-semibold text-gray-900">{{ selectedAnnouncement.author_name }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-100 rounded-lg">
                  <Calendar class="w-5 h-5 text-gray-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500">Published</p>
                  <p class="font-semibold text-gray-900">{{ formatDate(selectedAnnouncement.published_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Basic reveal animation */
.reveal { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
.reveal-show { opacity: 1 !important; transform: translateY(0) !important; }

/* Mobile slide/fade for drawer */
.slide-fade-enter-active, .slide-fade-leave-active { transition: all .25s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* Floating logo subtle animation */
@keyframes float { 0% { transform: translateY(0); } 50% { transform: translateY(-3px); } 100% { transform: translateY(0); } }
.animate-float { animation: float 4s ease-in-out infinite; }

/* Reduce motion preference */
@media (prefers-reduced-motion: reduce) {
  * { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; scroll-behavior: auto !important; }
}
</style>
