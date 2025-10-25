<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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

interface Bet {
    id: number;
    stake: string;
    selection: string;
    status: string;
    payout: string;
    created_at: string;
    event: Event;
    selected_team?: string;
}

interface Stats {
    total_bets: number;
    pending_count: number;
    won_count: number;
    lost_count: number;
    total_staked: string;
    total_returns: string;
}

const props = defineProps<{
    pendingBets: Bet[];
    settledBets: Bet[];
    stats: Stats;
}>();

const activeTab = ref<'pending' | 'settled'>('pending');

const cancelForm = useForm({});

const cancelBet = (betId: number) => {
    if (confirm('Are you sure you want to cancel this bet? Your stake will be refunded.')) {
        cancelForm.post(route('bets.cancel', betId), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(parseFloat(amount));
};

const getSelectionLabel = (bet: Bet) => {
    if (bet.selection === 'home') {
        return bet.event.home_team;
    } else if (bet.selection === 'away') {
        return bet.event.away_team;
    }
    return 'Draw';
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'won':
            return 'bg-green-100 text-green-800';
        case 'lost':
            return 'bg-red-100 text-red-800';
        case 'void':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const profit = parseFloat(props.stats.total_returns) - parseFloat(props.stats.total_staked);
const profitColor = profit >= 0 ? 'text-green-600' : 'text-red-600';
</script>

<template>
    <Head title="My Bets" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Bets</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Bets</div>
                        <div class="text-3xl font-bold">{{ stats.total_bets }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Pending</div>
                        <div class="text-3xl font-bold text-yellow-600">{{ stats.pending_count }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Won</div>
                        <div class="text-3xl font-bold text-green-600">{{ stats.won_count }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Profit/Loss</div>
                        <div class="text-3xl font-bold" :class="profitColor">
                            {{ formatCurrency(profit) }}
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 flex gap-2">
                    <button
                        @click="activeTab = 'pending'"
                        :class="[
                            'px-6 py-3 rounded-lg font-medium transition',
                            activeTab === 'pending'
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        Pending Bets ({{ pendingBets.length }})
                    </button>
                    <button
                        @click="activeTab = 'settled'"
                        :class="[
                            'px-6 py-3 rounded-lg font-medium transition',
                            activeTab === 'settled'
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100',
                        ]"
                    >
                        Settled Bets ({{ settledBets.length }})
                    </button>
                </div>

                <!-- Pending Bets -->
                <div v-if="activeTab === 'pending'" class="space-y-4">
                    <div
                        v-for="bet in pendingBets"
                        :key="bet.id"
                        class="bg-white rounded-lg shadow overflow-hidden"
                    >
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xl">{{ bet.event.sport.icon }}</span>
                                        <span class="text-sm text-gray-600">{{ bet.event.sport.name }}</span>
                                    </div>
                                    <div class="font-semibold text-lg text-gray-900">
                                        {{ bet.event.home_team }} vs {{ bet.event.away_team }}
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ formatDate(bet.event.starts_at) }}
                                    </div>
                                </div>
                                <span
                                    :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusColor(bet.status)]"
                                >
                                    {{ bet.status.toUpperCase() }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 rounded-lg p-4">
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Your Pick</div>
                                    <div class="font-medium">{{ getSelectionLabel(bet) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Stake</div>
                                    <div class="font-medium">{{ formatCurrency(bet.stake) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Potential Payout</div>
                                    <div class="font-medium text-green-600">{{ formatCurrency(bet.payout) }}</div>
                                </div>
                                <div class="flex items-end">
                                    <button
                                        @click="cancelBet(bet.id)"
                                        :disabled="cancelForm.processing"
                                        class="text-sm text-red-600 hover:text-red-800 font-medium disabled:opacity-50"
                                    >
                                        Cancel Bet
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="pendingBets.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
                        <div class="text-6xl mb-4">🎲</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            No pending bets
                        </h3>
                        <p class="text-gray-500 mb-6">
                            Browse events and place your first bet!
                        </p>
                        <Link
                            :href="route('events.index')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Browse Events
                        </Link>
                    </div>
                </div>

                <!-- Settled Bets -->
                <div v-if="activeTab === 'settled'" class="space-y-4">
                    <div
                        v-for="bet in settledBets"
                        :key="bet.id"
                        class="bg-white rounded-lg shadow overflow-hidden"
                    >
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xl">{{ bet.event.sport.icon }}</span>
                                        <span class="text-sm text-gray-600">{{ bet.event.sport.name }}</span>
                                    </div>
                                    <div class="font-semibold text-lg text-gray-900">
                                        {{ bet.event.home_team }} vs {{ bet.event.away_team }}
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ formatDate(bet.event.starts_at) }}
                                    </div>
                                </div>
                                <span
                                    :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusColor(bet.status)]"
                                >
                                    {{ bet.status.toUpperCase() }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 rounded-lg p-4">
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Your Pick</div>
                                    <div class="font-medium">{{ getSelectionLabel(bet) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Stake</div>
                                    <div class="font-medium">{{ formatCurrency(bet.stake) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">
                                        {{ bet.status === 'won' ? 'Payout' : 'Potential Payout' }}
                                    </div>
                                    <div :class="['font-medium', bet.status === 'won' ? 'text-green-600' : 'text-gray-600']">
                                        {{ formatCurrency(bet.payout) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 mb-1">Result</div>
                                    <div :class="['font-semibold', bet.status === 'won' ? 'text-green-600' : 'text-red-600']">
                                        {{ bet.status === 'won' ? '+' : '-' }}{{ formatCurrency(bet.status === 'won' ? parseFloat(bet.payout) - parseFloat(bet.stake) : bet.stake) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="settledBets.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
                        <div class="text-6xl mb-4">📊</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            No settled bets yet
                        </h3>
                        <p class="text-gray-500">
                            Your betting history will appear here once events are settled.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
