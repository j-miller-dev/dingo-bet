<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon: string;
    events_count?: number;
}

interface Event {
    id: number;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    sport: Sport;
}

const props = defineProps<{
    events: Event[];
    sports: Sport[];
    selectedSport?: string;
}>();

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const groupedEvents = computed(() => {
    const groups: Record<string, Event[]> = {};

    props.events.forEach((event) => {
        const sportName = event.sport.name;
        if (!groups[sportName]) {
            groups[sportName] = [];
        }
        groups[sportName].push(event);
    });

    return groups;
});
</script>

<template>
    <Head title="Events" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upcoming Events</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Sport Filters -->
                <div class="mb-6 flex flex-wrap gap-2">
                    <Link
                        :href="route('events.index')"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition',
                            !selectedSport
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        All Sports
                    </Link>
                    <Link
                        v-for="sport in sports"
                        :key="sport.id"
                        :href="route('events.index', { sport: sport.slug })"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition flex items-center gap-2',
                            selectedSport === sport.slug
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        <span>{{ sport.icon }}</span>
                        <span>{{ sport.name }}</span>
                        <span
                            v-if="sport.events_count"
                            class="text-xs opacity-75"
                        >
                            ({{ sport.events_count }})
                        </span>
                    </Link>
                </div>

                <!-- Events List -->
                <div v-if="events.length > 0" class="space-y-6">
                    <div
                        v-for="(sportEvents, sportName) in groupedEvents"
                        :key="sportName"
                        class="bg-white rounded-lg shadow overflow-hidden"
                    >
                        <!-- Sport Header -->
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
                                <span>{{ sportEvents[0].sport.icon }}</span>
                                <span>{{ sportName }}</span>
                            </h3>
                        </div>

                        <!-- Events in this sport -->
                        <div class="divide-y divide-gray-200">
                            <Link
                                v-for="event in sportEvents"
                                :key="event.id"
                                :href="route('events.show', event.id)"
                                class="block px-6 py-4 hover:bg-gray-50 transition"
                            >
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 text-lg">
                                            {{ event.home_team }} vs {{ event.away_team }}
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ formatDate(event.starts_at) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800"
                                        >
                                            Bet Now →
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="bg-white rounded-lg shadow p-12 text-center"
                >
                    <div class="text-6xl mb-4">🏆</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        No upcoming events
                    </h3>
                    <p class="text-gray-500">
                        Check back later for new betting opportunities!
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
