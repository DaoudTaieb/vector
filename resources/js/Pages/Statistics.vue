<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../Layouts/MainLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    date: String,
    from_month: String,
    to_month: String,
    from_date: String,
    to_date: String,
    etat: String,
    site_id: [String, Number],
    tab: String,
    journal_id: [String, Number],
    journeeSessions: Array,
    journeeTotaux: Object,
    journalLignes: Array,
    journalEntete: Object,
    rapportMensuel: Object,
    rapportMensuelDetail: Array,
    sessionsJournal: Array,
    ventesRubrique: Array,
    chiffreAffaireFamille: Array,
    chiffreAffaireVendeur: Array,
    etatJourneeJournal: Object,
    articlesVendus: Array,

    caisses: Array,
    sites: Array,




});

const activeTab = ref(props.tab || (props.journal_id ? 'journal' : 'journee'));
const date = ref(props.date || new Date().toISOString().slice(0, 10));
const from_month = ref(props.from_month);
const to_month = ref(props.to_month);
const from_date = ref(props.from_date);
const to_date = ref(props.to_date);
const etat = ref(props.etat || 'Tous');
const site_id = ref(props.site_id || '');

// Synchroniser les refs quand les props changent (après une requête Inertia)
watch(() => [props.date, props.from_month, props.to_month, props.site_id, props.tab, props.from_date, props.to_date, props.etat], ([newDate, newFrom, newTo, newSite, newTab, newFD, newTD, newEtat]) => {
    if (newDate) date.value = newDate;
    if (newFrom) from_month.value = newFrom;
    if (newTo) to_month.value = newTo;
    site_id.value = newSite || '';
    if (newTab) activeTab.value = newTab;
    if (newFD) from_date.value = newFD;
    if (newTD) to_date.value = newTD;
    if (newEtat) etat.value = newEtat;
});




// Quand on arrive avec un journal_id (clic "Voir journal"), afficher l'onglet Journal caisse
watch(() => props.journal_id, (id) => {
    if (id) activeTab.value = 'journal';
}, { immediate: true });

const formatCurrency = (val) => {
    if (val == null) return '0,000';
    return new Intl.NumberFormat('fr-TN', { minimumFractionDigits: 3, maximumFractionDigits: 3 }).format(Number(val));
};

const formatDate = (d) => {
    if (!d) return '—';
    const x = new Date(d);
    return x.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const loadWithDate = () => {
    router.get('/statistiques', { date: date.value, from_month: from_month.value, to_month: to_month.value, site_id: site_id.value, from_date: from_date.value, to_date: to_date.value, etat: etat.value }, { preserveState: true });
};

const loadWithMonthRange = () => {
    router.get('/statistiques', { date: date.value, from_month: from_month.value, to_month: to_month.value, site_id: site_id.value, from_date: from_date.value, to_date: to_date.value, etat: etat.value }, { preserveState: true });
};

const loadJournalRange = () => {
    router.get('/statistiques', { from_date: from_date.value, to_date: to_date.value, site_id: site_id.value, etat: etat.value, tab: 'journal' }, { preserveState: true });
};

const applyDate = () => loadWithDate();
const applyMonthRange = () => loadWithMonthRange();
const applyJournalRange = () => loadJournalRange();

const selectJournal = (id) => {
    router.get('/statistiques', { date: date.value, from_month: from_month.value, to_month: to_month.value, site_id: site_id.value, journal_id: id || undefined, tab: 'journal', from_date: from_date.value, to_date: to_date.value, etat: etat.value }, { preserveState: true });
};

const openEtatJournee = (session) => {
    const sessionDate = session.dateouverture ? session.dateouverture.substring(0, 10) : date.value;
    router.get('/statistiques', { 
        date: sessionDate, 
        site_id: session.siteid, 
        tab: 'journee', 
        from_month: from_month.value, 
        to_month: to_month.value, 
        from_date: from_date.value, 
        to_date: to_date.value, 
        etat: etat.value 
    }, { preserveState: true });
};

// Actualisation automatique quand la date ou le mois change (dynamique)
watch(date, () => { if (activeTab.value === 'journee' || activeTab.value === 'journal') debouncedLoadDate(); });
watch([from_month, to_month, site_id], () => { if (activeTab.value === 'mensuel') debouncedLoadMonthRange(); });
watch([from_date, to_date, site_id, etat], () => { if (activeTab.value === 'journal') debouncedLoadJournalRange(); });

const debouncedLoadDate = debounce(loadWithDate, 400);
const debouncedLoadMonthRange = debounce(loadWithMonthRange, 400);
const debouncedLoadJournalRange = debounce(loadJournalRange, 400);


</script>

<template>
    <MainLayout>
        <Head title="Statistiques" />
        <div class="p-4 sm:p-6 lg:p-6 pb-8 safe-bottom max-w-[1920px] mx-auto">
            <header class="mb-8">
                <h2 class="text-2xl md:text-3xl font-extrabold text-brand-charcoal tracking-tight mb-1">
                    {{ activeTab === 'journee' ? 'État de journée' : (activeTab === 'journal' ? 'Journal de caisse' : 'Rapport mensuel') }}
                </h2>
                <p class="text-sm md:text-base text-[#706f6c] font-medium">Analyse et rapports détaillés</p>
            </header>

            <!-- État de journée -->
            <div v-show="activeTab === 'journee'" class="space-y-8">
                <!-- Filtres premium integration -->
                <div class="bg-white p-6 rounded-[2rem] flex flex-wrap items-center gap-8 border border-[#e3e3e0] shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Période</span>
                        <input v-model="date" type="date" class="rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal" />
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Site</span>
                        <select v-model="site_id" class="min-w-[200px] rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal">
                            <option value="">Tous les sites</option>
                            <option v-for="s in sites" :key="s.siteid" :value="s.siteid">{{ s.libelle }}</option>
                        </select>
                    </div>
                    <button @click="applyDate" class="bg-brand-gold text-white p-3 rounded-2xl hover:opacity-90 shadow-lg shadow-brand-gold/20 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <div class="ml-auto text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Recette Nette Day</p>
                        <p class="text-2xl font-black text-brand-charcoal">{{ formatCurrency(etatJourneeJournal?.recettenet || 0) }}</p>
                    </div>
                </div>

                <!-- Grid Layout (matching screenshot) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- SECTION 1: CHIFFRE D'AFFAIRE / FAMILLE -->
                    <div class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-sm overflow-hidden flex flex-col h-[500px]">
                        <div class="px-4 py-3 border-b border-[#f0f0f0] bg-[#fdfdfc]">
                            <h3 class="text-[11px] font-black text-brand-gold uppercase tracking-[0.1em]">Chiffre d'affaire / Famille</h3>
                        </div>
                        <div class="overflow-auto flex-grow custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-white shadow-sm z-10">
                                    <tr class="text-[10px] font-bold text-slate-500 bg-slate-50 border-b border-[#f0f0f0] uppercase">
                                        <th class="px-2 py-2">Code</th>
                                        <th class="px-2 py-2">Famille</th>
                                        <th class="px-2 py-2 text-right">PV TTC</th>
                                        <th class="px-2 py-2 text-right">Qte</th>
                                        <th class="px-2 py-2 text-right">Remise</th>
                                        <th class="px-2 py-2 text-right">Montant</th>
                                    </tr>
                                    <!-- Ligne de Totaux Section -->
                                    <tr class="bg-brand-gold/5 font-black text-brand-gold">
                                        <td colspan="3" class="px-2 py-2 text-right text-[9px]">TOTAL</td>
                                        <td class="px-2 py-2 text-right">{{ chiffreAffaireFamille?.reduce((a,b)=>a+Number(b.qte),0).toFixed(3) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatCurrency(chiffreAffaireFamille?.reduce((a,b)=>a+Number(b.remise),0)) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatCurrency(chiffreAffaireFamille?.reduce((a,b)=>a+Number(b.montant),0)) }}</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f5f5f4] text-[11px]">
                                    <tr v-for="(f, i) in chiffreAffaireFamille" :key="i" class="hover:bg-[#fcfcfb]">
                                        <td class="px-2 py-2 font-mono text-[#a8a29e]">{{ f.code }}</td>
                                        <td class="px-2 py-2 font-bold text-brand-charcoal uppercase">{{ f.famille }}</td>
                                        <td class="px-2 py-2 text-right font-mono">{{ formatCurrency(f.puttc) }}</td>
                                        <td class="px-2 py-2 text-right font-bold">{{ Number(f.qte).toFixed(3) }}</td>
                                        <td class="px-2 py-2 text-right font-mono text-[#a8a29e]">{{ formatCurrency(f.remise) }}</td>
                                        <td class="px-2 py-2 text-right font-black">{{ formatCurrency(f.montant) }}</td>
                                    </tr>
                                    <tr v-if="!chiffreAffaireFamille?.length">
                                        <td colspan="6" class="px-4 py-24 text-center text-slate-300 italic">Vide</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION 2: VENTES ET RETOURS EFFECTUES -->
                    <div class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-sm overflow-hidden flex flex-col h-[500px]">
                        <div class="px-4 py-3 border-b border-[#f0f0f0] bg-[#fdfdfc] flex justify-between items-center">
                            <h3 class="text-[11px] font-black text-brand-gold uppercase tracking-[0.1em]">Ventes et Retours Effectuées</h3>
                            <span class="text-[9px] font-black text-slate-300">SECTION 1</span>
                        </div>
                        <div class="overflow-auto flex-grow custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-white shadow-sm z-10">
                                    <tr class="text-[9px] font-bold text-slate-500 bg-slate-50 border-b border-[#f0f0f0] uppercase">
                                        <th class="px-2 py-2">Réf</th>
                                        <th class="px-2 py-2">Désignation</th>
                                        <th class="px-2 py-2">Coul/T</th>
                                        <th class="px-2 py-2 text-right">PV TTC</th>
                                        <th class="px-2 py-2 text-right">Qte</th>
                                        <th class="px-2 py-2 text-right">Rem</th>
                                        <th class="px-2 py-2 text-right">Montant</th>
                                    </tr>
                                    <!-- Ligne de Totaux Section -->
                                    <tr class="bg-brand-gold/5 font-black text-brand-gold">
                                        <td colspan="4" class="px-2 py-2 text-right text-[9px]">TOTAL</td>
                                        <td class="px-2 py-2 text-right">{{ ventesRubrique?.reduce((a,b)=>a+Number(b.qte),0).toFixed(3) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatCurrency(ventesRubrique?.reduce((a,b)=>a+Number(b.remise),0)) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatCurrency(ventesRubrique?.reduce((a,b)=>a+Number(b.montant),0)) }}</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f5f5f4] text-[10px]">
                                    <tr v-for="(v, i) in ventesRubrique" :key="i" class="hover:bg-[#fcfcfb]">
                                        <td class="px-2 py-2 font-mono text-[#a8a29e]">{{ v.reference }}</td>
                                        <td class="px-2 py-2 font-bold text-brand-charcoal">{{ v.designation }}</td>
                                        <td class="px-2 py-2 text-[#706f6c]">{{ v.couleur }}<span v-if="v.couleur && v.taille"> / </span>{{ v.taille }}</td>
                                        <td class="px-2 py-2 text-right font-mono">{{ formatCurrency(v.puttcnet) }}</td>
                                        <td class="px-2 py-2 text-right font-bold">{{ Number(v.qte).toFixed(3) }}</td>
                                        <td class="px-2 py-2 text-right text-[#a8a29e]">{{ formatCurrency(v.remise) }}</td>
                                        <td class="px-2 py-2 text-right font-black text-brand-charcoal">{{ formatCurrency(v.montant) }}</td>
                                    </tr>
                                    <tr v-if="!ventesRubrique?.length">
                                        <td colspan="7" class="px-4 py-24 text-center">
                                            <p class="text-slate-300 italic uppercase tracking-widest text-[9px]">Aucune vente enregistrée</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bottom Triple Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- SECTION 3: CAISSE -->
                    <div class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-sm overflow-hidden pb-4">
                        <div class="px-6 py-4 border-b border-[#f0f0f0] bg-[#fdfdfc]">
                            <h3 class="text-xs font-black text-brand-gold uppercase tracking-[0.1em]">Caisse</h3>
                        </div>
                        <div class="p-2">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="text-[9px] font-black text-slate-400 uppercase">
                                    <tr class="border-b">
                                        <th class="px-4 py-3">Intitulé</th>
                                        <th class="px-4 py-3 text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-brand-charcoal">
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 font-bold">VENTES REGLEES</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.ventereglee || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">ACOMPTES ( N.V )</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.acomptenewticket || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">ACOMPTES ( A.V )</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalacompteticket || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">RETOURS</td><td class="px-4 py-3 text-right font-mono text-rose-500">{{ formatCurrency(etatJourneeJournal?.retourticket || 0) }}</td></tr>
                                    <tr class="bg-brand-gold/5 font-black"><td class="px-4 py-3">RECETTE BRUTE</td><td class="px-4 py-3 text-right font-mono text-brand-gold text-lg">{{ formatCurrency(etatJourneeJournal?.recettebrut || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">DEPENSES DIV</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.depensedivers || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">ACOMPTES PERSONNELS</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.acomptepersonnel || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Commission PERSONNELS</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalcommission || 0) }}</td></tr>
                                    <tr class="bg-brand-charcoal text-white font-black"><td class="px-4 py-3">RECETTE NETTE</td><td class="px-4 py-3 text-right font-mono text-brand-gold text-lg">{{ formatCurrency(etatJourneeJournal?.recettenet || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3">RECETTE PHYSIQUE</td><td class="px-4 py-3 text-right font-mono text-sky-600 font-bold">{{ formatCurrency(etatJourneeJournal?.recettephysique || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Coupon</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalcoupon || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Crédit</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalcredit || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Crédit Acompte</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalcreditacompte || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Offre</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totaloffre || 0) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION 4: DETAILS RECETTE -->
                    <div class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-sm overflow-hidden pb-4">
                        <div class="px-6 py-4 border-b border-[#f0f0f0] bg-[#fdfdfc]">
                            <h3 class="text-xs font-black text-brand-gold uppercase tracking-[0.1em]">Détails Recette</h3>
                        </div>
                        <div class="p-2">
                             <table class="w-full text-left text-xs border-collapse">
                                <thead class="text-[9px] font-black text-slate-400 uppercase">
                                    <tr class="border-b">
                                        <th class="px-4 py-3">Mode de Règlement</th>
                                        <th class="px-4 py-3 text-right">Montant</th>
                                    </tr>
                                    <tr class="bg-brand-gold font-black text-white">
                                        <td class="px-4 py-3">TOTAL RECETTE</td>
                                        <td class="px-4 py-3 text-right text-lg border-l border-white/20">{{ formatCurrency((etatJourneeJournal?.totalespece || 0) + (etatJourneeJournal?.totalcheque || 0) + (etatJourneeJournal?.totaltpe || 0) + (etatJourneeJournal?.totalregautre || 0)) }}</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-brand-charcoal font-medium">
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-4 font-black text-brand-gold">Espèces</td><td class="px-4 py-4 text-right font-mono text-lg">{{ formatCurrency(etatJourneeJournal?.totalespece || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3">Chèques</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalcheque || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 text-blue-600">Carte Crédit (TPE)</td><td class="px-4 py-3 text-right font-mono text-blue-600 font-bold">{{ formatCurrency(etatJourneeJournal?.totaltpe || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Bons d'achat / Avoirs</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalregavoir || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Bons Convention</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalbonconvention || 0) }}</td></tr>
                                    <tr class="hover:bg-slate-50"><td class="px-4 py-3 opacity-60">Autres</td><td class="px-4 py-3 text-right font-mono">{{ formatCurrency(etatJourneeJournal?.totalregautre || 0) }}</td></tr>
                                    <tr class="bg-slate-50/50 italic border-t-2"><td class="px-4 py-3 text-[10px] text-slate-400">Espèces Net physique</td><td class="px-4 py-3 text-right font-mono text-slate-400">{{ formatCurrency(etatJourneeJournal?.totalespecenet || 0) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SECTION 5: CA / VENDEUR -->
                    <div class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-sm overflow-hidden pb-4">
                        <div class="px-6 py-4 border-b border-[#f0f0f0] bg-[#fdfdfc]">
                            <h3 class="text-xs font-black text-brand-gold uppercase tracking-[0.1em]">Chiffre d'affaire / Vendeur</h3>
                        </div>
                        <div class="p-2">
                             <table class="w-full text-left text-xs border-collapse">
                                <thead class="text-[9px] font-black text-slate-400 uppercase">
                                    <tr class="border-b">
                                        <th class="px-4 py-3">Employé / Vendeur</th>
                                        <th class="px-4 py-3 text-right">Montant</th>
                                    </tr>
                                    <tr class="bg-brand-gold/10 font-bold text-brand-gold">
                                        <td class="px-4 py-3 uppercase tracking-tighter">Somme par vendeur</td>
                                        <td class="px-4 py-3 text-right text-lg">{{ formatCurrency(chiffreAffaireVendeur?.reduce((a,b)=>a+Number(b.montant),0)) }}</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-brand-charcoal">
                                    <tr v-for="(v, i) in chiffreAffaireVendeur" :key="i" class="hover:bg-slate-50 group">
                                        <td class="px-4 py-4 font-bold group-hover:text-brand-gold transition-colors">{{ v.vendeur || 'Sans Vendeur' }}</td>
                                        <td class="px-4 py-4 text-right font-mono font-black text-brand-gold italic text-base">{{ formatCurrency(v.montant) }}</td>
                                    </tr>
                                    <tr v-if="!chiffreAffaireVendeur?.length">
                                        <td colspan="2" class="px-6 py-20 text-center text-slate-300 italic tracking-[0.2em] uppercase text-[9px]">Aucune donnée vendeur</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal de caisse -->
            <div v-show="activeTab === 'journal'" class="space-y-8">
                <!-- Filtres Horizonaux Intégrés au Theme -->
                <div class="bg-white p-6 rounded-[2rem] flex flex-wrap items-center gap-8 border border-[#e3e3e0] shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Du</span>
                        <input v-model="from_date" type="date" class="rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal" />
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">AU</span>
                        <input v-model="to_date" type="date" class="rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal" />
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Site</span>
                        <select v-model="site_id" class="min-w-[180px] rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal">
                            <option value="">Tous les sites</option>
                            <option v-for="s in sites" :key="s.siteid" :value="s.siteid">{{ s.libelle }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Etat</span>
                        <select v-model="etat" class="min-w-[140px] rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal">
                            <option value="Tous">Tous</option>
                            <option value="Clôturé">Clôturé</option>
                            <option value="Non Clôturé">Non Clôturé</option>
                        </select>
                    </div>
                    <button @click="applyJournalRange" class="bg-brand-gold text-white p-3 rounded-2xl hover:opacity-90 shadow-lg shadow-brand-gold/20 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button v-if="journal_id" @click="selectJournal(null)" class="text-[10px] font-bold text-brand-gold hover:underline uppercase tracking-widest ml-auto">Fermer les détails</button>
                </div>

                <!-- Vue Liste des Sessions Style Premium -->
                <div v-if="!journal_id" class="bg-white rounded-[2.5rem] border border-[#e3e3e0] shadow-2xl shadow-brand-charcoal/5 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left min-w-max whitespace-nowrap border-collapse">
                            <thead>
                                <tr class="text-[10px] font-bold text-brand-charcoal/70 bg-[#fdfdfc] border-b border-[#f0f0f0] uppercase tracking-wider">
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Id Session</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Point de Vente</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Ouverture</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Clôture</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Agence</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Fond Caisse</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Recette Brute</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Espèces</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Chèques</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">TPE</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Bon Conv</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Avoir Repris</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Autre</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Dépenses</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Recette Nette</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Acomptes</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Crédit Acompte</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">Comp. Acompte</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0] text-center">TOT Acomptes</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Etat</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">Caissier</th>
                                    <th class="px-4 py-6 border-r border-[#f0f0f0]">ID Site</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f5f5f4] text-xs">
                                <!-- Ligne de totaux intégrée style Premium -->
                                <tr class="bg-brand-gold/5 font-black text-brand-gold border-b-2 border-brand-gold/10">
                                    <td colspan="5" class="px-6 py-4 text-right tracking-widest text-[10px]">TOTAL PÉRIODE</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.fondcaisse),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.recettebrut),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.especes),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.cheques),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.tpe),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.bon_conv),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.avoir_repris),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.autre),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.depenses),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono text-brand-gold">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.recettenet),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.acomptes),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.credit_acompte),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.comp_acompte),0)) }}</td>
                                    <td class="px-4 py-4 border-r border-brand-gold/10 text-center font-mono">{{ formatCurrency(sessionsJournal.reduce((a,b)=>a+Number(b.tot_acomptes),0)) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                                <!-- Lignes de données -->
                                <tr v-for="s in sessionsJournal" :key="s.journalcaisseid" class="hover:bg-[#fdfdfc] cursor-pointer transition-all group border-l-4 border-transparent hover:border-brand-gold" @click="openEtatJournee(s)">
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] font-mono text-[#a8a29e] group-hover:text-brand-gold transition-colors">{{ s.journalcaisseid }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] font-bold text-brand-charcoal">{{ s.site_libelle }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-[#706f6c]">{{ formatDate(s.dateouverture) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-[#706f6c]">{{ formatDate(s.datecloture) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] font-semibold text-[#706f6c]">{{ s.agence_pv }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.fondcaisse) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.recettebrut) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.especes) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.cheques) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.tpe) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.bon_conv) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.avoir_repris) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.autre) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.depenses) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono font-black text-brand-charcoal group-hover:text-brand-gold">{{ formatCurrency(s.recettenet) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.acomptes) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.credit_acompte) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono">{{ formatCurrency(s.comp_acompte) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center font-mono font-bold">{{ formatCurrency(s.tot_acomptes) }}</td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] text-center">
                                        <div v-if="s.isclosed" class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-brand-gold/10 text-brand-gold">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <div v-else class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-100 text-slate-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 border-r border-[#f0f0f0] font-medium text-brand-charcoal">{{ s.caissier_nom }} {{ s.caissier_prenom }}</td>
                                    <td class="px-4 py-4 text-[#a8a29e] font-mono text-[10px]">{{ s.siteid }}</td>
                                </tr>
                                <tr v-if="!sessionsJournal?.length">
                                    <td colspan="22" class="px-6 py-32 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="w-16 h-16 rounded-full bg-[#fdfdfc] flex items-center justify-center border border-[#f0f0f0]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                                            </div>
                                            <p class="text-slate-400 font-medium italic">Aucune donnée trouvée pour cette période.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Vue Détail des Lignes (Si journal_id sélectionné) -->
                <div v-else class="space-y-4">
                    <div v-if="journalEntete" class="bg-blue-50 p-6 rounded-[2rem] border border-blue-100 shadow-sm flex justify-between items-center">
                        <div>
                            <p class="text-[10px] font-black uppercase text-blue-400 tracking-widest mb-1">Session Détails</p>
                            <h4 class="text-xl font-bold text-slate-800">{{ journalEntete.caisse_libelle }} — {{ journalEntete.site_libelle }}</h4>
                            <p class="text-sm text-slate-500">{{ formatDate(journalEntete.dateouverture) }} au {{ formatDate(journalEntete.datecloture) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-brand-gold">{{ formatCurrency(journalEntete.montantcloture) }}</p>
                            <p class="text-xs font-bold text-slate-400">Total clôture</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#f0f0f0] flex justify-between items-center">
                            <h3 class="font-bold text-brand-charcoal">Lignes du journal</h3>
                            <button @click="selectJournal(null)" class="text-xs font-bold text-slate-400 hover:text-brand-gold transition-colors">Retour à la liste</button>
                        </div>
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left min-w-max whitespace-nowrap border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-[#fdfdfc] border-b border-[#f0f0f0]">
                                        <th class="px-6 py-4">Libellé</th>
                                        <th class="px-6 py-4 hidden sm:table-cell">Section</th>
                                        <th class="px-6 py-4 text-right">Valeur</th>
                                        <th class="px-6 py-4 text-right hidden md:table-cell">Achat TTC</th>
                                        <th class="px-6 py-4 text-right hidden md:table-cell">Marge TTC</th>
                                        <th class="px-6 py-4 text-right hidden lg:table-cell">Qte</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f0f0f0]">
                                    <tr v-for="l in journalLignes" :key="l.journalcaissedetid" class="hover:bg-[#fcfcfb]">
                                        <td class="px-6 py-3 text-sm font-medium">{{ l.libelle }}</td>
                                        <td class="px-6 py-3 text-xs text-slate-400 hidden sm:table-cell">{{ l.section_libelle || '—' }}</td>
                                        <td class="px-6 py-3 text-right font-mono font-bold">{{ formatCurrency(l.valeur) }}</td>
                                        <td class="px-6 py-3 text-right font-mono text-slate-400 hidden md:table-cell">{{ formatCurrency(l.achatttc) }}</td>
                                        <td class="px-6 py-3 text-right font-mono text-slate-400 hidden md:table-cell">{{ formatCurrency(l.margettc) }}</td>
                                        <td class="px-6 py-3 text-right text-xs hidden lg:table-cell">{{ l.qte != null ? l.qte : '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'mensuel'" class="space-y-6">
                <!-- Filtres premium style -->
                <div class="bg-white p-6 rounded-[2rem] flex flex-wrap items-center gap-8 border border-[#e3e3e0] shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">DU</span>
                        <input v-model="from_month" type="month" class="rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal" />
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">AU</span>
                        <input v-model="to_month" type="month" class="rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal" />
                    </div>
                    <div class="flex items-center gap-3 flex-grow">
                        <span class="text-[10px] font-black tracking-[0.2em] uppercase text-brand-gold">Site</span>
                        <select v-model="site_id" class="flex-grow max-w-[300px] rounded-xl border-[#e3e3e0] px-4 py-2 text-sm font-bold focus:border-brand-gold focus:ring-0 text-brand-charcoal">
                            <option value="">Tous les sites</option>
                            <option v-for="s in sites" :key="s.siteid" :value="s.siteid">{{ s.libelle }}</option>
                        </select>
                    </div>
                    <button @click="applyMonthRange" class="bg-brand-gold text-white p-3 rounded-2xl hover:opacity-90 shadow-lg shadow-brand-gold/20 transition-all active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left min-w-max whitespace-nowrap border-collapse">
                            <thead class="sticky top-0 bg-white shadow-sm z-10">
                                <tr class="text-[10px] font-bold text-slate-500 bg-slate-50 border-b border-[#f0f0f0] uppercase tracking-wider">
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-4 py-4 text-right">CA Vente</th>
                                    <th class="px-4 py-4 text-right">CA Achat</th>
                                    <th class="px-4 py-4 text-right">Reglement CLT</th>
                                    <th class="px-4 py-4 text-right">Reglement FRS</th>
                                    <th class="px-4 py-4 text-right">Depense</th>
                                    <th class="px-4 py-4 text-right">Resultat</th>
                                </tr>
                                <!-- Ligne de totaux en haut -->
                                <tr class="bg-brand-gold/5 font-black text-brand-gold">
                                    <td class="px-6 py-3 text-[11px] uppercase tracking-wider">TOTAUX</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.vente),0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.achat),0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.reglement_clt),0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.reglement_frs),0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.depense),0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-[13px]">{{ formatCurrency((rapportMensuelDetail || []).reduce((a,b)=>a+Number(b.resultat),0)) }}</td>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f5f5f4] text-[11px]">
                                <!-- Lignes de données -->
                                <tr v-for="r in rapportMensuelDetail" :key="r.date" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-brand-charcoal">{{ r.date }}</td>
                                    <td class="px-4 py-4 text-right font-mono text-[#a8a29e]">{{ formatCurrency(r.vente) }}</td>
                                    <td class="px-4 py-4 text-right font-mono text-[#a8a29e]">{{ formatCurrency(r.achat) }}</td>
                                    <td class="px-4 py-4 text-right font-mono text-brand-charcoal font-bold">{{ formatCurrency(r.reglement_clt) }}</td>
                                    <td class="px-4 py-4 text-right font-mono text-[#a8a29e]">{{ formatCurrency(r.reglement_frs) }}</td>
                                    <td class="px-4 py-4 text-right font-mono text-[#a8a29e]">{{ formatCurrency(r.depense) }}</td>
                                    <td class="px-4 py-4 text-right font-mono font-black text-sm" :class="r.resultat >= 0 ? 'text-emerald-600' : 'text-rose-500'">{{ formatCurrency(r.resultat) }}</td>
                                </tr>
                                <tr v-if="!rapportMensuelDetail?.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">Aucune donnée pour cette période.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
