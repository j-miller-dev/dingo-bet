<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

interface Transaction {
    id: number;
    type: 'credit' | 'debit';
    amount: string;
    balance_after: string;
    description: string;
    created_at: string;
}

interface Wallet {
    id: number;
    balance: string;
    currency: string;
}

defineProps<{
    wallet: Wallet;
    transactions: Transaction[];
}>();

const showAddMoney = ref(false);

const form = useForm({
    amount: '',
});

const addPlayMoney = () => {
    form.post(route('wallet.add-play-money'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddMoney.value = false;
        },
    });
};

const formatCurrency = (amount: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(parseFloat(amount));
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Wallet" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Wallet</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Balance Card -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-8 mb-6">
                    <div class="text-white">
                        <p class="text-sm uppercase tracking-wide opacity-80">Available Balance</p>
                        <p class="text-5xl font-bold mt-2">{{ formatCurrency(wallet.balance) }}</p>
                        <p class="text-sm mt-4 opacity-80">Play Money (Demo Account)</p>
                    </div>
                    <div class="mt-6">
                        <PrimaryButton
                            @click="showAddMoney = !showAddMoney"
                            class="bg-white text-blue-600 hover:bg-gray-100"
                        >
                            {{ showAddMoney ? 'Cancel' : '+ Add Play Money' }}
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Add Money Form -->
                <div v-if="showAddMoney" class="bg-white rounded-lg shadow mb-6 p-6">
                    <h3 class="text-lg font-semibold mb-4">Add Play Money</h3>
                    <form @submit.prevent="addPlayMoney" class="flex gap-4 items-start">
                        <div class="flex-1">
                            <TextInput
                                v-model="form.amount"
                                type="number"
                                placeholder="Enter amount (max $10,000)"
                                min="1"
                                max="10000"
                                step="0.01"
                                class="w-full"
                            />
                            <InputError :message="form.errors.amount" class="mt-2" />
                        </div>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                        >
                            Add Money
                        </PrimaryButton>
                    </form>
                </div>

                <!-- Transaction History -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Transaction History</h3>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="transaction in transactions"
                            :key="transaction.id"
                            class="px-6 py-4 hover:bg-gray-50 transition"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">
                                        {{ transaction.description }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ formatDate(transaction.created_at) }}
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <p
                                        :class="[
                                            'font-semibold text-lg',
                                            transaction.type === 'credit'
                                                ? 'text-green-600'
                                                : 'text-red-600',
                                        ]"
                                    >
                                        {{ transaction.type === 'credit' ? '+' : '-' }}
                                        {{ formatCurrency(transaction.amount) }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Balance: {{ formatCurrency(transaction.balance_after) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="transactions.length === 0"
                            class="px-6 py-12 text-center text-gray-500"
                        >
                            No transactions yet
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
