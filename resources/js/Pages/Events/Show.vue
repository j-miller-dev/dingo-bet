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

interface Event {
    id: number;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    sport: Sport;
}

interface Wallet {
    id: number;
    balance: string;
}

const props = defineProps<{
    event: Event;
    wallet: Wallet | null;
}>();

const selectedTeam = ref<'home' | 'away' | null>(null);

const form = useForm({
    event_id: props.event.id,
    selection: '',
    stake: '',
});

const potentialPayout = computed(() => {
    const stake = parseFloat(form.stake);
    if (isNaN(stake) || stake <= 0) return 0;
    // Simple 2x payout for Phase 3 (we'll add real odds in Phase 4)
    return stake * 2;
});

const canBet = computed(() => {
    return props.event.status === 'upcoming' && new Date(props.event.starts_at) > new Date();
});

const selectTeam = (team: 'home' | 'away') => {
    selectedTeam.value = team;
    form.selection = team;
};

const placeBet = () => {
    form.post(route('bets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('stake');
            selectedTeam.value = null;
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

                        <!-- Betting Section -->
                        <div class="border-t border-gray-200 pt-8">
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">
                                Place Your Bet
                            </h3>

                            <!-- Betting closed message -->
                            <div v-if="!canBet" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                <p class="text-yellow-800 font-medium">
                                    Betting is closed for this event
                                </p>
                                <p class="text-sm text-yellow-600 mt-2">
                                    This event has already started or is no longer accepting bets.
                                </p>
                            </div>

                            <!-- Betting form -->
                            <div v-else>
                                <!-- Team Selection -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <button
                                        @click="selectTeam('home')"
                                        :class="[
                                            'p-6 rounded-lg border-2 transition font-medium',
                                            selectedTeam === 'home'
                                                ? 'border-blue-600 bg-blue-50 text-blue-900'
                                                : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300',
                                        ]"
                                    >
                                        <div class="text-lg">{{ event.home_team }}</div>
                                        <div class="text-sm mt-2 opacity-75">2.00x payout</div>
                                    </button>
                                    <button
                                        @click="selectTeam('away')"
                                        :class="[
                                            'p-6 rounded-lg border-2 transition font-medium',
                                            selectedTeam === 'away'
                                                ? 'border-blue-600 bg-blue-50 text-blue-900'
                                                : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300',
                                        ]"
                                    >
                                        <div class="text-lg">{{ event.away_team }}</div>
                                        <div class="text-sm mt-2 opacity-75">2.00x payout</div>
                                    </button>
                                </div>

                                <!-- Stake Input -->
                                <div v-if="selectedTeam" class="space-y-4">
                                    <div>
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

                                    <!-- Bet Summary -->
                                    <div v-if="form.stake && parseFloat(form.stake) > 0" class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between mb-2">
                                            <span class="text-gray-600">Stake:</span>
                                            <span class="font-medium">{{ formatCurrency(form.stake) }}</span>
                                        </div>
                                        <div class="flex justify-between mb-2">
                                            <span class="text-gray-600">Potential Payout:</span>
                                            <span class="font-semibold text-green-600">
                                                {{ formatCurrency(potentialPayout) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
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
                                        Place Bet on {{ selectedTeam === 'home' ? event.home_team : event.away_team }}
                                    </PrimaryButton>

                                    <p class="text-xs text-gray-500 text-center">
                                        Note: For Phase 3, all bets have a simple 2x payout. Real odds coming in Phase 4!
                                    </p>
                                </div>

                                <div v-else class="text-center py-8 text-gray-500">
                                    Select a team above to place your bet
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
