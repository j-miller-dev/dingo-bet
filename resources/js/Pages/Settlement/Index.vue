<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Sport {
    id: number;
    name: string;
    icon: string;
}

interface Event {
    id: number;
    sport: Sport;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    result: string | null;
    pending_bets_count: number;
    total_pending_stake: number;
}

const props = defineProps<{
    unsettledEvents: Event[];
    recentlySettled: Event[];
}>();

const settling = ref<number | null>(null);
const voiding = ref<number | null>(null);

const settleEvent = (eventId: number, winner: string) => {
    if (!confirm(`Are you sure you want to settle this event as ${winner.toUpperCase()} win?`)) {
        return;
    }

    settling.value = eventId;
    router.post(
        route('settlement.settle', eventId),
        { winner },
        {
            onFinish: () => {
                settling.value = null;
            },
        }
    );
};

const voidEvent = (eventId: number) => {
    if (!confirm('Are you sure you want to void this event? All bets will be refunded.')) {
        return;
    }

    voiding.value = eventId;
    router.post(
        route('settlement.void', eventId),
        {},
        {
            onFinish: () => {
                voiding.value = null;
            },
        }
    );
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const getResultLabel = (result: string | null) => {
    if (!result) return 'N/A';
    if (result === 'home') return 'Home Win';
    if (result === 'away') return 'Away Win';
    return 'Draw';
};
</script>

<template>
    <Head title="Event Settlement" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Event Settlement
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Unsettled Events -->
                <div class="mb-8">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        Events to Settle ({{ unsettledEvents.length }})
                    </h3>

                    <div v-if="unsettledEvents.length === 0" class="rounded-lg bg-white p-6 shadow">
                        <p class="text-gray-500">No events awaiting settlement.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="event in unsettledEvents"
                            :key="event.id"
                            class="rounded-lg bg-white p-6 shadow"
                        >
                            <div class="mb-4 flex items-start justify-between">
                                <div>
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-2xl">{{ event.sport.icon }}</span>
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ event.sport.name }}
                                        </span>
                                        <span
                                            class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800"
                                        >
                                            {{ event.status }}
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-900">
                                        {{ event.home_team }} vs {{ event.away_team }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        {{ formatDateTime(event.starts_at) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Pending Bets</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ event.pending_bets_count }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatCurrency(event.total_pending_stake) }} at stake
                                    </p>
                                </div>
                            </div>

                            <!-- Settlement Actions -->
                            <div class="flex flex-wrap gap-2">
                                <button
                                    @click="settleEvent(event.id, 'home')"
                                    :disabled="settling === event.id"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700 disabled:opacity-50"
                                >
                                    {{ settling === event.id ? 'Settling...' : 'Home Win' }}
                                </button>
                                <button
                                    @click="settleEvent(event.id, 'away')"
                                    :disabled="settling === event.id"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ settling === event.id ? 'Settling...' : 'Away Win' }}
                                </button>
                                <button
                                    @click="settleEvent(event.id, 'draw')"
                                    :disabled="settling === event.id"
                                    class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:opacity-50"
                                >
                                    {{ settling === event.id ? 'Settling...' : 'Draw' }}
                                </button>
                                <button
                                    @click="voidEvent(event.id)"
                                    :disabled="voiding === event.id"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                                >
                                    {{ voiding === event.id ? 'Voiding...' : 'Void Event' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recently Settled Events -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        Recently Settled ({{ recentlySettled.length }})
                    </h3>

                    <div v-if="recentlySettled.length === 0" class="rounded-lg bg-white p-6 shadow">
                        <p class="text-gray-500">No recently settled events.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="event in recentlySettled"
                            :key="event.id"
                            class="rounded-lg bg-white p-6 shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-2xl">{{ event.sport.icon }}</span>
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ event.sport.name }}
                                        </span>
                                        <span
                                            class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800"
                                        >
                                            {{ event.status }}
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-900">
                                        {{ event.home_team }} vs {{ event.away_team }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        {{ formatDateTime(event.starts_at) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Result</p>
                                    <p class="text-xl font-bold text-gray-900">
                                        {{ getResultLabel(event.result) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
