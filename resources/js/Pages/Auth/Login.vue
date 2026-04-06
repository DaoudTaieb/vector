<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion" />
    <div class="min-h-screen min-h-[100dvh] flex flex-col items-center justify-center bg-[#fdfdfc] px-4 py-6 safe-top safe-bottom safe-left safe-right">
        <div class="w-full max-w-md flex-1 flex flex-col justify-center">
            <!-- Brand Logo / Identity -->
            <div class="flex flex-col items-center mb-12 animate-in fade-in slide-in-from-bottom-4 duration-1000">
                <div class="w-24 h-24 mb-6 relative">
                    <img :src="'/logo'" alt="VECTOR" class="w-full h-full object-contain rounded-2xl shadow-md" />
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tighter text-brand-charcoal mb-1 text-center">VECTOR</h1>
                <p class="text-brand-gold font-bold uppercase tracking-[0.2em] text-xs">STE L'Innovation De Mode</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white p-5 sm:p-6 md:p-8 rounded-[1.5rem] sm:rounded-[2rem] border border-[#e3e3e0] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.05)] animate-in fade-in zoom-in-95 duration-700 delay-200">
                <p class="text-center text-[#1b1b18] font-semibold mb-8">Accès Sécurisé</p>
                
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#706f6c] ml-1">Mot de passe</label>
                            <input 
                                id="password"
                                v-model="form.password"
                                type="password" 
                                required 
                                autofocus
                                placeholder="••••••••"
                                class="w-full px-5 py-4 rounded-2xl bg-[#f8f8f7] border border-transparent focus:bg-white focus:border-brand-gold focus:ring-4 focus:ring-brand-gold/10 transition-all duration-200 outline-none text-lg font-mono tracking-widest"
                            />
                            <div v-if="form.errors.login" class="text-red-500 text-sm font-medium mt-1 ml-1">
                                {{ form.errors.login }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-2 ml-1">
                            <label class="flex items-center cursor-pointer group">
                                <div class="relative">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.remember"
                                        class="sr-only"
                                    />
                                    <div class="w-10 h-6 bg-[#e3e3e0] rounded-full shadow-inner transition-colors duration-200" :class="{'bg-brand-gold': form.remember}"></div>
                                    <div class="absolute inset-y-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200" :class="{'translate-x-4': form.remember}"></div>
                                </div>
                                <span class="ml-3 text-sm font-semibold text-[#706f6c] group-hover:text-brand-charcoal transition-colors uppercase tracking-wider">Se souvenir de moi</span>
                            </label>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-brand-gold text-white py-4 rounded-2xl font-bold transition-all duration-300 hover:bg-brand-gold-dark hover:shadow-[0_8px_20px_-4px_rgba(194,154,91,0.4)] active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 group"
                        >
                            <span>Se Connecter</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <footer class="mt-12 text-center text-[#706f6c] text-sm animate-in fade-in duration-1000 delay-500">
                &copy; {{ new Date().getFullYear() }} Vector. All rights reserved.
            </footer>
        </div>
    </div>
</template>

<style>
@keyframes in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-in {
    animation: in 1s ease forwards;
}
.delay-200 { animation-delay: 0.2s; }
.delay-500 { animation-delay: 0.5s; }
</style>
