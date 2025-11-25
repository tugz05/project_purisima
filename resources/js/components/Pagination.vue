<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});
</script>

<template>
    <nav :class="['flex items-center justify-between', props.class]" aria-label="Pagination">
        <div class="flex items-center space-x-2">
            <!-- Previous Button -->
            <Button
                v-if="links[0]?.url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="links[0].url">
                    <ChevronLeft class="h-4 w-4 mr-1" />
                    Previous
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="sm"
                disabled
            >
                <ChevronLeft class="h-4 w-4 mr-1" />
                Previous
            </Button>

            <!-- Page Numbers -->
            <div class="flex items-center space-x-1">
                <template v-for="(link, index) in links.slice(1, -1)" :key="index">
                    <Button
                        v-if="link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        as-child
                    >
                        <Link :href="link.url">
                            {{ link.label }}
                        </Link>
                    </Button>
                    <span
                        v-else
                        class="px-3 py-2 text-sm text-gray-500"
                    >
                        {{ link.label }}
                    </span>
                </template>
            </div>

            <!-- Next Button -->
            <Button
                v-if="links[links.length - 1]?.url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="links[links.length - 1].url">
                    Next
                    <ChevronRight class="h-4 w-4 ml-1" />
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="sm"
                disabled
            >
                Next
                <ChevronRight class="h-4 w-4 ml-1" />
            </Button>
        </div>
    </nav>
</template>
