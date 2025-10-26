<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon: string;
    events_count: number;
    api_group: string;
}

interface SportGroup {
    name: string;
    sports: Sport[];
    total_events: number;
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
    sportGroups: SportGroup[];
    selectedSport?: string;
    selectedGroup?: string;
}>();

const showGroups = ref(false);

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

const eventsByDate = computed(() => {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const nextWeek = new Date(today);
    nextWeek.setDate(nextWeek.getDate() + 7);

    const groups: Record<string, Event[]> = {
        'Today': [],
        'Tomorrow': [],
        'This Week': [],
        'Later': [],
    };

    props.events.forEach((event) => {
        const eventDate = new Date(event.starts_at);
        const eventDay = new Date(eventDate.getFullYear(), eventDate.getMonth(), eventDate.getDate());

        if (eventDay.getTime() === today.getTime()) {
            groups['Today'].push(event);
        } else if (eventDay.getTime() === tomorrow.getTime()) {
            groups['Tomorrow'].push(event);
        } else if (eventDate < nextWeek) {
            groups['This Week'].push(event);
        } else {
            groups['Later'].push(event);
        }
    });

    // Filter out empty groups
    return Object.fromEntries(
        Object.entries(groups).filter(([_, events]) => events.length > 0)
    );
});

const topCategories = computed(() => {
    return props.sportGroups.slice(0, 6); // Show top 6 categories
});

const filterByGroup = (groupName: string) => {
    router.get(route('events.index', { group: groupName }));
};

const filterBySport = (sportSlug: string) => {
    router.get(route('events.index', { sport: sportSlug }));
};

const clearFilters = () => {
    router.get(route('events.index'));
};
</script>

<template>
    <Head title="Events" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upcoming Events</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Quick Category Navigation -->
                <div class="mb-6 bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-900">Quick Filters</h3>
                        <button
                            @click="showGroups = !showGroups"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            {{ showGroups ? 'Hide All Sports' : 'View All Sports' }}
                        </button>
                    </div>

                    <!-- Top Categories -->
                    <div class="flex flex-wrap gap-2 mb-2">
                        <button
                            v-for="group in topCategories"
                            :key="group.name"
                            @click="filterByGroup(group.name)"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition text-sm',
                                selectedGroup === group.name
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                            ]"
                        >
                            {{ group.name }} ({{ group.total_events }})
                        </button>
                    </div>
                </div>

                <!-- Sport Groups Browser -->
                <div v-if="showGroups" class="mb-6 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Browse by Sport Category</h3>

                    <div class="space-y-4">
                        <div v-for="group in sportGroups" :key="group.name" class="border rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold text-gray-900">{{ group.name }}</h4>
                                <button
                                    @click="filterByGroup(group.name)"
                                    class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition"
                                >
                                    View All {{ group.total_events }} Events
                                </button>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="sport in group.sports"
                                    :key="sport.id"
                                    @click="filterBySport(sport.slug)"
                                    :class="[
                                        'px-3 py-2 rounded-lg text-sm font-medium transition',
                                        selectedSport === sport.slug
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    ]"
                                >
                                    {{ sport.icon }} {{ sport.name }}
                                    <span class="ml-1 text-xs opacity-75">({{ sport.events_count }})</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Filters -->
                <div v-if="selectedGroup || selectedSport" class="mb-4 flex items-center gap-2">
                    <span class="text-sm text-gray-600">Filters:</span>
                    <span v-if="selectedGroup" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                        {{ selectedGroup }}
                    </span>
                    <span v-if="selectedSport" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                        {{ selectedSport }}
                    </span>
                    <button
                        @click="clearFilters"
                        class="text-sm text-red-600 hover:text-red-800 underline"
                    >
                        Clear All
                    </button>
                </div>

                <!-- Events Summary -->
                <div class="mb-6">
                    <p class="text-gray-600">
                        Showing <span class="font-semibold">{{ events.length }}</span> upcoming events
                    </p>
                </div>

                <!-- Events List Grouped by Date -->
                <div v-if="events.length > 0" class="space-y-8">
                    <div v-for="(dateEvents, dateLabel) in eventsByDate" :key="dateLabel">
                        <!-- Date Header -->
                        <div class="mb-4">
                            <h2 class="text-2xl font-bold text-gray-900">{{ dateLabel }}</h2>
                            <p class="text-sm text-gray-500">{{ dateEvents.length }} events</p>
                        </div>

                        <!-- Events Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Link
                                v-for="event in dateEvents"
                                :key="event.id"
                                :href="route('events.show', event.id)"
                                class="bg-white rounded-lg shadow p-5 hover:shadow-lg transition group"
                            >
                                <!-- Sport Badge -->
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xl">{{ event.sport.icon }}</span>
                                    <span class="text-xs font-medium text-gray-500">{{ event.sport.name }}</span>
                                </div>

                                <!-- Teams -->
                                <div class="mb-3">
                                    <div class="font-semibold text-base text-gray-900">
                                        {{ event.home_team }}
                                    </div>
                                    <div class="text-gray-500 text-sm my-1">vs</div>
                                    <div class="font-semibold text-base text-gray-900">
                                        {{ event.away_team }}
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="text-sm text-gray-600 mb-3">
                                    {{ formatDate(event.starts_at) }}
                                </div>

                                <!-- Action -->
                                <div class="pt-3 border-t border-gray-200">
                                    <button class="text-blue-600 group-hover:text-blue-800 text-sm font-medium">
                                        View Odds →
                                    </button>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- No Events -->
                <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="text-6xl mb-4">🏆</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        No upcoming events
                    </h3>
                    <p class="text-gray-500 mb-4">
                        Import events from TheOddsAPI to start betting
                    </p>
                    <p class="text-sm text-gray-400">
                        Run: <code class="bg-gray-100 px-2 py-1 rounded">php artisan events:import</code>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
