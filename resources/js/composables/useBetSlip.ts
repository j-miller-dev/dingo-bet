import { ref, computed } from 'vue';

export interface BetSlipSelection {
    eventId: number;
    eventName: string;
    oddsId: number;
    oddsName: string;
    oddsValue: number;
    marketName: string;
    stake?: number;
}

const selections = ref<BetSlipSelection[]>([]);
const isOpen = ref(false);

export function useBetSlip() {
    const addSelection = (selection: BetSlipSelection) => {
        // Remove existing selection for same event
        selections.value = selections.value.filter(s => s.eventId !== selection.eventId);
        // Add new selection
        selections.value.push(selection);
        isOpen.value = true;
    };

    const removeSelection = (eventId: number) => {
        selections.value = selections.value.filter(s => s.eventId !== eventId);
    };

    const clearAll = () => {
        selections.value = [];
    };

    const toggleSlip = () => {
        isOpen.value = !isOpen.value;
    };

    const combinedOdds = computed(() => {
        if (selections.value.length === 0) return 1;
        return selections.value.reduce((acc, sel) => acc * sel.oddsValue, 1);
    });

    const totalStake = computed(() => {
        const stake = selections.value[0]?.stake || 0;
        return stake;
    });

    const potentialPayout = computed(() => {
        return totalStake.value * combinedOdds.value;
    });

    return {
        selections,
        isOpen,
        addSelection,
        removeSelection,
        clearAll,
        toggleSlip,
        combinedOdds,
        totalStake,
        potentialPayout,
    };
}
