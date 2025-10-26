<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useBetSlip } from '@/composables/useBetSlip';

const betSlip = useBetSlip();
const stake = ref(10);
const submitting = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const placeBets = () => {
    if (betSlip.selections.value.length === 0) return;

    submitting.value = true;

    // Place each bet individually
    const promises = betSlip.selections.value.map(selection => {
        return new Promise((resolve, reject) => {
            router.post(
                route('bets.store'),
                {
                    event_id: selection.eventId,
                    odds_id: selection.oddsId,
                    stake: stake.value,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(true),
                    onError: () => reject(),
                }
            );
        });
    });

    Promise.all(promises)
        .then(() => {
            betSlip.clearAll();
            stake.value = 10;
        })
        .finally(() => {
            submitting.value = false;
        });
};

const updateAllStakes = (newStake: number) => {
    stake.value = newStake;
    betSlip.selections.value.forEach(sel => {
        sel.stake = newStake;
    });
};
</script>

<template>
    <!-- Floating Bet Slip Button -->
    <button
        v-if="!betSlip.isOpen.value && betSlip.selections.value.length > 0"
        @click="betSlip.toggleSlip"
        class="fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-full shadow-lg hover:bg-blue-700 transition z-40"
    >
        Bet Slip ({{ betSlip.selections.value.length }})
    </button>

    <!-- Bet Slip Panel -->
    <div
        v-if="betSlip.isOpen.value"
        class="fixed bottom-0 right-0 w-full md:w-96 bg-white shadow-2xl z-50 max-h-[80vh] overflow-y-auto"
    >
        <!-- Header -->
        <div class="bg-blue-600 text-white p-4 flex justify-between items-center sticky top-0">
            <h3 class="font-semibold text-lg">
                Bet Slip ({{ betSlip.selections.value.length }})
            </h3>
            <button @click="betSlip.toggleSlip" class="text-white hover:text-gray-200">
                ✕
            </button>
        </div>

        <!-- Selections -->
        <div v-if="betSlip.selections.value.length > 0" class="p-4 space-y-3">
            <div
                v-for="selection in betSlip.selections.value"
                :key="selection.eventId"
                class="border rounded-lg p-3 relative"
            >
                <button
                    @click="betSlip.removeSelection(selection.eventId)"
                    class="absolute top-2 right-2 text-gray-400 hover:text-red-600"
                >
                    ✕
                </button>

                <div class="pr-6">
                    <div class="text-xs text-gray-500 mb-1">{{ selection.marketName }}</div>
                    <div class="font-semibold text-sm">{{ selection.eventName }}</div>
                    <div class="text-sm text-gray-700 mt-1">{{ selection.oddsName }}</div>
                    <div class="text-lg font-bold text-blue-600 mt-1">
                        {{ selection.oddsValue.toFixed(2) }}
                    </div>
                </div>
            </div>

            <!-- Stake Input -->
            <div class="border-t pt-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Stake (per bet)
                </label>
                <input
                    type="number"
                    v-model.number="stake"
                    @input="updateAllStakes(stake)"
                    min="1"
                    step="10"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <!-- Totals -->
            <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Bets:</span>
                    <span class="font-semibold">{{ betSlip.selections.value.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Stake per bet:</span>
                    <span class="font-semibold">{{ formatCurrency(stake) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Stake:</span>
                    <span class="font-semibold">{{ formatCurrency(stake * betSlip.selections.value.length) }}</span>
                </div>
                <div class="border-t pt-2 flex justify-between">
                    <span class="font-semibold">Potential Win:</span>
                    <span class="font-bold text-green-600 text-lg">
                        {{ formatCurrency(betSlip.selections.value.reduce((sum, sel) => sum + (stake * sel.oddsValue), 0)) }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button
                    @click="placeBets"
                    :disabled="submitting || betSlip.selections.value.length === 0"
                    class="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50"
                >
                    {{ submitting ? 'Placing...' : 'Place Bets' }}
                </button>
                <button
                    @click="betSlip.clearAll"
                    class="px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-8 text-center text-gray-500">
            <div class="text-4xl mb-2">🎲</div>
            <p>Add bets to get started</p>
        </div>
    </div>

    <!-- Overlay -->
    <div
        v-if="betSlip.isOpen.value"
        @click="betSlip.toggleSlip"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
    ></div>
</template>
