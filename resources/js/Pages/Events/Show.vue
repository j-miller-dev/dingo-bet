<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon: string;
}

interface Event {
    id: number;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    sport: Sport;
}

defineProps<{
    event: Event;
}>();

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`${event.home_team} vs ${event.away_team}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('events.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Events
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Event Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Sport Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <div class="flex items-center gap-2 text-white">
                            <span class="text-2xl">{{ event.sport.icon }}</span>
                            <span class="text-lg font-medium">{{ event.sport.name }}</span>
                        </div>
                    </div>

                    <!-- Match Details -->
                    <div class="p-8">
                        <!-- Teams -->
                        <div class="text-center mb-8">
                            <div class="text-4xl font-bold text-gray-900 mb-4">
                                {{ event.home_team }}
                                <span class="text-gray-400 mx-4">vs</span>
                                {{ event.away_team }}
                            </div>
                            <div class="text-lg text-gray-600">
                                {{ formatDate(event.starts_at) }}
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex justify-center mb-8">
                            <span
                                :class="[
                                    'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium',
                                    event.status === 'upcoming'
                                        ? 'bg-green-100 text-green-800'
                                        : event.status === 'live'
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ event.status.toUpperCase() }}
                            </span>
                        </div>

                        <!-- Betting Section (Placeholder) -->
                        <div class="border-t border-gray-200 pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">
                                Place Your Bet
                            </h3>
                            <div class="bg-gray-50 rounded-lg p-6 text-center">
                                <p class="text-gray-600 mb-4">
                                    Betting functionality coming in Phase 3!
                                </p>
                                <p class="text-sm text-gray-500">
                                    You'll be able to bet on {{ event.home_team }} or {{ event.away_team }} soon.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
