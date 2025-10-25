<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

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

const selectedOdds = ref<Odds | null>(null);

const form = useForm({
    event_id: props.event.id,
    odds_id: 0,
    stake: '',
});

const potentialPayout = computed(() => {
    const stake = parseFloat(form.stake);
    if (isNaN(stake) || stake <= 0 || !selectedOdds.value) return 0;
    return stake * parseFloat(selectedOdds.value.value);
});

const canBet = computed(() => {
    return props.event.status === 'upcoming' && new Date(props.event.starts_at) > new Date();
});

const selectOdds = (odds: Odds) => {
    selectedOdds.value = odds;
    form.odds_id = odds.id;
};

const placeBet = () => {
    form.post(route('bets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('stake');
            selectedOdds.value = null;
        },
    });
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
                <div v-if="!canBet" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
                    <p class="text-yellow-800 font-medium">
                        Betting is closed for this event
                    </p>
                    <p class="text-sm text-yellow-600 mt-2">
                        This event has already started or is no longer accepting bets.
                    </p>
                </div>

                <!-- Markets Grid -->
                <div v-if="canBet && event.markets && event.markets.length > 0">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
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
                                    @click="selectOdds(odds)"
                                    :class="[
                                        'w-full p-3 rounded-lg border-2 transition font-medium text-left flex justify-between items-center',
                                        selectedOdds?.id === odds.id
                                            ? 'border-blue-600 bg-blue-50 text-blue-900'
                                            : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300',
                                    ]"
                                >
                                    <span>{{ odds.name }}</span>
                                    <span class="text-lg font-bold">{{ parseFloat(odds.value).toFixed(2) }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bet Slip -->
                    <div v-if="selectedOdds" class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">
                            Bet Slip
                        </h3>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm text-blue-600 font-medium">Your Selection</div>
                                    <div class="font-semibold text-gray-900">{{ selectedOdds.name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-blue-600">Odds</div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ parseFloat(selectedOdds.value).toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Stake Amount
                            </label>
                            <TextInput
                                v-model="form.stake"
                                type="number"
                                placeholder="Enter stake amount"
                                min="1"
                                :max="wallet?.balance || 10000"
                                step="0.01"
                                class="w-full"
                            />
                            <InputError :message="form.errors.stake || form.errors.bet" class="mt-2" />
                        </div>

                        <!-- Payout Summary -->
                        <div v-if="form.stake && parseFloat(form.stake) > 0" class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Stake:</span>
                                <span class="font-medium">{{ formatCurrency(form.stake) }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Odds:</span>
                                <span class="font-medium">{{ parseFloat(selectedOdds.value).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2">
                                <span class="text-gray-900 font-semibold">Potential Payout:</span>
                                <span class="font-bold text-green-600 text-lg">
                                    {{ formatCurrency(potentialPayout) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-gray-600">Potential Profit:</span>
                                <span class="font-medium text-green-600">
                                    {{ formatCurrency(potentialPayout - parseFloat(form.stake)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Place Bet Button -->
                        <PrimaryButton
                            @click="placeBet"
                            :disabled="form.processing || !form.stake || parseFloat(form.stake) <= 0"
                            class="w-full justify-center text-lg py-3"
                        >
                            Place Bet
                        </PrimaryButton>
                    </div>

                    <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                        <p class="text-gray-600">
                            Select any option above to place your bet
                        </p>
                    </div>
                </div>

                <!-- No markets available -->
                <div v-else-if="canBet" class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                    <p class="text-gray-600">
                        No betting markets available for this event yet.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
