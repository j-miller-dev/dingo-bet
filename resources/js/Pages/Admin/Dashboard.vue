<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Stats {
    total_users: number;
    active_users: number;
    total_events: number;
    total_bets: number;
    total_staked: number;
    total_payouts: number;
    platform_profit: number;
}

interface BetStats {
    pending: number;
    won: number;
    lost: number;
    void: number;
}

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface Bet {
    id: number;
    stake: string;
    status: string;
    created_at: string;
    user: User;
    event: {
        home_team: string;
        away_team: string;
        sport: {
            name: string;
            icon: string;
        };
    };
    odds?: {
        name: string;
        value: string;
    };
}

interface Event {
    id: number;
    home_team: string;
    away_team: string;
    starts_at: string;
    status: string;
    bets_count: number;
    sport: {
        name: string;
        icon: string;
    };
}

interface SportStat {
    id: number;
    name: string;
    icon: string;
    events_count: number;
    upcoming_events_count: number;
}

const props = defineProps<{
    stats: Stats;
    betStats: BetStats;
    recentBets: Bet[];
    recentUsers: User[];
    unsettledEvents: Event[];
    sportStats: SportStat[];
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'won':
            return 'bg-green-100 text-green-800';
        case 'lost':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('admin.users')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        Manage Users
                    </Link>
                    <Link
                        :href="route('admin.settlement')"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                    >
                        Settle Events
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Platform Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Users</div>
                        <div class="text-3xl font-bold">{{ stats.total_users }}</div>
                        <div class="text-xs text-gray-500 mt-2">Active (7d): {{ stats.active_users }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Events</div>
                        <div class="text-3xl font-bold">{{ stats.total_events }}</div>
                        <div class="text-xs text-gray-500 mt-2">
                            {{ unsettledEvents.length }} need settlement
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Bets</div>
                        <div class="text-3xl font-bold">{{ stats.total_bets }}</div>
                        <div class="text-xs text-gray-500 mt-2">Pending: {{ betStats.pending }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm text-gray-600 mb-1">Platform Profit</div>
                        <div
                            class="text-3xl font-bold"
                            :class="stats.platform_profit >= 0 ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ formatCurrency(stats.platform_profit) }}
                        </div>
                        <div class="text-xs text-gray-500 mt-2">
                            Staked: {{ formatCurrency(stats.total_staked) }}
                        </div>
                    </div>
                </div>

                <!-- Bet Distribution -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Bet Status Distribution</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ betStats.pending }}</div>
                            <div class="text-sm text-gray-600">Pending</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ betStats.won }}</div>
                            <div class="text-sm text-gray-600">Won</div>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ betStats.lost }}</div>
                            <div class="text-sm text-gray-600">Lost</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-600">{{ betStats.void }}</div>
                            <div class="text-sm text-gray-600">Voided</div>
                        </div>
                    </div>
                </div>

                <!-- Sport Statistics -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Sports Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="sport in sportStats" :key="sport.id" class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-3xl mb-2">{{ sport.icon }}</div>
                            <div class="font-semibold">{{ sport.name }}</div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ sport.events_count }} events ({{ sport.upcoming_events_count }} upcoming)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Events Needing Settlement -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Events Needing Settlement</h3>
                            <Link
                                :href="route('admin.settlement')"
                                class="text-sm text-blue-600 hover:text-blue-800"
                            >
                                View All
                            </Link>
                        </div>
                        <div v-if="unsettledEvents.length === 0" class="text-center py-8 text-gray-500">
                            No events to settle
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="event in unsettledEvents.slice(0, 5)"
                                :key="event.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xl">{{ event.sport.icon }}</span>
                                    <span class="text-sm text-gray-600">{{ event.sport.name }}</span>
                                </div>
                                <div class="font-semibold text-sm">
                                    {{ event.home_team }} vs {{ event.away_team }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ event.bets_count }} pending bets
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Bets -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Bets</h3>
                        <div class="space-y-3">
                            <div
                                v-for="bet in recentBets"
                                :key="bet.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-semibold text-sm">{{ bet.user.name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ bet.event.home_team }} vs {{ bet.event.away_team }}
                                        </div>
                                    </div>
                                    <span :class="['px-2 py-1 rounded text-xs font-medium', getStatusColor(bet.status)]">
                                        {{ bet.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-600">
                                    <span>{{ formatCurrency(parseFloat(bet.stake)) }}</span>
                                    <span>{{ formatDate(bet.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Recent Registrations</h3>
                            <Link
                                :href="route('admin.users')"
                                class="text-sm text-blue-600 hover:text-blue-800"
                            >
                                View All
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="user in recentUsers"
                                :key="user.id"
                                class="border rounded-lg p-3"
                            >
                                <div class="font-semibold text-sm">{{ user.name }}</div>
                                <div class="text-xs text-gray-500">{{ user.email }}</div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Joined {{ formatDate(user.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
