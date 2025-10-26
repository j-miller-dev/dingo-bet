<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useBetSlip } from '@/composables/useBetSlip';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon: string;
}

interface Odds {
    id: number;
    market_id: number;
    name: string;
    value: string;
    active: boolean;
}

interface Market {
    id: number;
    event_id: number;
    name: string;
    type: string;
    active: boolean;
    odds: Odds[];
}

interface Event {
    id: number;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    sport: Sport;
    markets: Market[];
}

interface Wallet {
    id: number;
    balance: string;
}

const props = defineProps<{
    event: Event;
    wallet: Wallet | null;
}>();

const betSlip = useBetSlip();

const canBet = () => {
    return props.event.status === 'upcoming' && new Date(props.event.starts_at) > new Date();
};

const addToBetSlip = (odds: Odds, market: Market) => {
    betSlip.addSelection({
        eventId: props.event.id,
        eventName: `${props.event.home_team} vs ${props.event.away_team}`,
        oddsId: odds.id,
        oddsName: odds.name,
        oddsValue: parseFloat(odds.value),
        marketName: market.name,
    });

    // Open the bet slip automatically
    if (!betSlip.isOpen.value) {
        betSlip.toggleSlip();
    }
};

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

const formatCurrency = (amount: string | number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(parseFloat(amount.toString()));
};

const getMarketIcon = (marketType: string) => {
    const icons: Record<string, string> = {
        match_winner: '🏆',
        over_under: '📊',
        both_teams_score: '⚽',
        handicap: '📐',
        total_sets: '🎾',
    };
    return icons[marketType] || '🎯';
};
</script>

<template>
    <Head :title="`${event.home_team} vs ${event.away_team}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <Link
                    :href="route('events.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Back to Events
                </Link>
                <div v-if="wallet" class="text-sm">
                    <span class="text-gray-600">Balance:</span>
                    <span class="font-semibold ml-2">{{ formatCurrency(wallet.balance) }}</span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Event Card -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
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
                        <div class="text-center mb-6">
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
                        <div class="flex justify-center">
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
                    </div>
                </div>

                <!-- Betting closed message -->
                <div v-if="!canBet()" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
                    <p class="text-yellow-800 font-medium">
                        Betting is closed for this event
                    </p>
                    <p class="text-sm text-yellow-600 mt-2">
                        This event has already started or is no longer accepting bets.
                    </p>
                </div>

                <!-- Markets Grid -->
                <div v-if="canBet() && event.markets && event.markets.length > 0">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Market Cards -->
                        <div
                            v-for="market in event.markets"
                            :key="market.id"
                            class="bg-white rounded-lg shadow overflow-hidden"
                        >
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <span>{{ getMarketIcon(market.type) }}</span>
                                    <span>{{ market.name }}</span>
                                </h3>
                            </div>
                            <div class="p-4 space-y-2">
                                <button
                                    v-for="odds in market.odds"
                                    :key="odds.id"
                                    @click="addToBetSlip(odds, market)"
                                    class="w-full p-3 rounded-lg border-2 border-gray-200 bg-white text-gray-900 hover:border-blue-600 hover:bg-blue-50 transition font-medium text-left flex justify-between items-center group"
                                >
                                    <span>{{ odds.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold">{{ parseFloat(odds.value).toFixed(2) }}</span>
                                        <span class="text-xs text-blue-600 opacity-0 group-hover:opacity-100 transition">+ Add</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No markets available -->
                <div v-else-if="canBet()" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                    <p class="text-gray-600">
                        No betting markets available for this event yet.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
