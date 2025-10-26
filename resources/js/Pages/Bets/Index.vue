<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon: string;
}

interface Market {
    id: number;
    name: string;
    type: string;
}

interface Odds {
    id: number;
    name: string;
    value: string;
    market: Market;
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
    odds_value: string;
    status: string;
    payout: string;
    created_at: string;
    event: Event;
    odds?: Odds;
    selected_team?: string;
}

interface Stats {
    total_bets: number;
    pending_count: number;
    won_count: number;
    lost_count: number;
    void_count: number;
    total_staked: number;
    total_returns: number;
    profit_loss: number;
    win_rate: number;
    average_stake: number;
    average_odds: number;
}

interface PerformanceData {
    date: string;
    profit: number;
}

interface Filters {
    sport_id?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}

const props = defineProps<{
    pendingBets: Bet[];
    settledBets: Bet[];
    sports: Sport[];
    stats: Stats;
    filters: Filters;
    performanceData: PerformanceData[];
}>();

const activeTab = ref<'pending' | 'settled'>('pending');
const showFilters = ref(false);

// Filter form
const filterForm = ref({
    sport_id: props.filters.sport_id || '',
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    router.get(route('bets.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.value = {
        sport_id: '',
        status: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return !!(filterForm.value.sport_id || filterForm.value.status || filterForm.value.date_from || filterForm.value.date_to);
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount: string | number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(typeof amount === 'string' ? parseFloat(amount) : amount);
};

const getSelectionLabel = (bet: Bet) => {
    if (bet.odds) {
        return bet.odds.name;
    }
    if (bet.selection === 'home') {
        return bet.event.home_team;
    } else if (bet.selection === 'away') {
        return bet.event.away_team;
    }
    return 'Draw';
};

const getMarketName = (bet: Bet) => {
    return bet.odds?.market.name || 'Match Winner';
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

const profitColor = computed(() => props.stats.profit_loss >= 0 ? 'text-green-600' : 'text-red-600');
</script>

<template>
    <Head title="My Bets" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Bets</h2>
                <button
                    @click="showFilters = !showFilters"
                    class="px-4 py-2 bg-white rounded-lg border border-gray-300 hover:bg-gray-50 transition"
                >
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                    <span v-if="hasActiveFilters" class="ml-2 px-2 py-1 bg-blue-600 text-white text-xs rounded-full">
                        Active
                    </span>
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters Panel -->
                <div v-if="showFilters" class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sport</label>
                            <select
                                v-model="filterForm.sport_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Sports</option>
                                <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                    {{ sport.icon }} {{ sport.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                v-model="filterForm.status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                                <option value="void">Void</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                            <input
                                type="date"
                                v-model="filterForm.date_from"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                            <input
                                type="date"
                                v-model="filterForm.date_to"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="applyFilters"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            Apply Filters
                        </button>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Enhanced Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Bets</div>
                        <div class="text-3xl font-bold">{{ stats.total_bets }}</div>
                        <div class="text-xs text-gray-500 mt-2">Pending: {{ stats.pending_count }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Win Rate</div>
                        <div class="text-3xl font-bold" :class="stats.win_rate >= 50 ? 'text-green-600' : 'text-gray-900'">
                            {{ stats.win_rate.toFixed(1) }}%
                        </div>
                        <div class="text-xs text-gray-500 mt-2">
                            {{ stats.won_count }}W / {{ stats.lost_count }}L
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Staked</div>
                        <div class="text-3xl font-bold">{{ formatCurrency(stats.total_staked) }}</div>
                        <div class="text-xs text-gray-500 mt-2">
                            Avg: {{ formatCurrency(stats.average_stake) }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Profit/Loss</div>
                        <div class="text-3xl font-bold" :class="profitColor">
                            {{ stats.profit_loss >= 0 ? '+' : '' }}{{ formatCurrency(stats.profit_loss) }}
                        </div>
                        <div class="text-xs text-gray-500 mt-2">
                            Returns: {{ formatCurrency(stats.total_returns) }}
                        </div>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div v-if="performanceData.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Performance (Last 30 Days)</h3>
                    <div class="h-48 flex items-end justify-between gap-1">
                        <div
                            v-for="(point, index) in performanceData.slice(-30)"
                            :key="index"
                            class="flex-1 bg-blue-500 rounded-t hover:bg-blue-600 transition relative group"
                            :style="{
                                height: `${Math.max(10, Math.abs(point.profit) / Math.max(...performanceData.map(p => Math.abs(p.profit))) * 100)}%`,
                                backgroundColor: point.profit >= 0 ? '#10b981' : '#ef4444'
                            }"
                            :title="`${point.date}: ${formatCurrency(point.profit)}`"
                        >
                            <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                                {{ point.date }}: {{ formatCurrency(point.profit) }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center text-sm text-gray-600">
                        Cumulative Profit/Loss Over Time
                    </div>
                </div>

                <!-- Additional Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 mb-1">Average Odds</div>
                        <div class="text-2xl font-bold">{{ stats.average_odds.toFixed(2) }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 mb-1">Biggest Win</div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatCurrency(Math.max(...pendingBets.concat(settledBets).map(b => parseFloat(b.payout) - parseFloat(b.stake)), 0)) }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-sm text-gray-600 mb-1">Voided Bets</div>
                        <div class="text-2xl font-bold text-gray-600">{{ stats.void_count }}</div>
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

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-xs text-gray-600 mb-2">{{ getMarketName(bet) }}</div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Your Pick</div>
                                        <div class="font-medium">{{ getSelectionLabel(bet) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Odds</div>
                                        <div class="font-medium">{{ bet.odds_value ? parseFloat(bet.odds_value).toFixed(2) : '2.00' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Stake</div>
                                        <div class="font-medium">{{ formatCurrency(bet.stake) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Potential Payout</div>
                                        <div class="font-medium text-green-600">{{ formatCurrency(bet.payout) }}</div>
                                    </div>
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

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-xs text-gray-600 mb-2">{{ getMarketName(bet) }}</div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Your Pick</div>
                                        <div class="font-medium">{{ getSelectionLabel(bet) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-600 mb-1">Odds</div>
                                        <div class="font-medium">{{ bet.odds_value ? parseFloat(bet.odds_value).toFixed(2) : '2.00' }}</div>
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
                                </div>
                                <div class="border-t border-gray-200 mt-3 pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Result</span>
                                        <span :class="['font-semibold text-lg', bet.status === 'won' ? 'text-green-600' : bet.status === 'lost' ? 'text-red-600' : 'text-gray-600']">
                                            {{ bet.status === 'won' ? '+' : bet.status === 'lost' ? '-' : '' }}{{ formatCurrency(bet.status === 'won' ? parseFloat(bet.payout) - parseFloat(bet.stake) : bet.stake) }}
                                        </span>
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
