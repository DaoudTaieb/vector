<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $fromMonth = $request->get('from_month', now()->startOfYear()->format('Y-m'));
        $toMonth = $request->get('to_month', now()->format('Y-m'));
        $fromDate = $request->get('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $siteId = $request->get('site_id');
        $etat = $request->get('etat', 'Tous'); // 'Tous', 'Clôturé', 'Non Clôturé'
        $tab = $request->get('tab');
        $journalId = $request->get('journal_id');




        // 1. Ventes et Retours (Rubrique 1)
        $ventesRubrique = DB::table('detctickets')
            ->join('ctickets', 'detctickets.cticketid', '=', 'ctickets.cticketid')
            ->join('produits', 'detctickets.produitid', '=', 'produits.produitid')
            ->leftJoin('produit2s', 'detctickets.produit2id', '=', 'produit2s.produit2id')
            ->leftJoin('tailles', 'produit2s.tailleid', '=', 'tailles.tailleid')
            ->leftJoin('couleurs', 'produit2s.couleurid', '=', 'couleurs.couleurid')
            ->whereDate('ctickets.cticketdate', $date)
            ->when($siteId, fn($q) => $q->where('ctickets.siteid', $siteId))
            ->select(
                'produits.produitcode as reference',
                'produits.produitlibelle as designation',
                'couleurs.couleurlibelle as couleur',
                'tailles.taillelibelle as taille',
                'detctickets.puttcnet',
                'detctickets.qte',
                'detctickets.remise',
                'detctickets.totalttcnet as montant'
            )
            ->get();

        // 2. Chiffre d'affaire par Famille
        $chiffreAffaireFamille = DB::table('detctickets')
            ->join('ctickets', 'detctickets.cticketid', '=', 'ctickets.cticketid')
            ->join('produits', 'detctickets.produitid', '=', 'produits.produitid')
            ->leftJoin('familles', 'produits.familleid', '=', 'familles.familleid')
            ->whereDate('ctickets.cticketdate', $date)
            ->when($siteId, fn($q) => $q->where('ctickets.siteid', $siteId))
            ->select(
                'familles.familleid as code',
                'familles.famillelibelle as famille',
                DB::raw('AVG(detctickets.puttcnet) as puttc'),
                DB::raw('SUM(detctickets.qte) as qte'),
                DB::raw('SUM(detctickets.remise) as remise'),
                DB::raw('SUM(detctickets.totalttcnet) as montant')
            )
            ->groupBy('familles.familleid', 'familles.famillelibelle')
            ->get();

        // 3. Chiffre d'affaire par Vendeur
        $chiffreAffaireVendeur = DB::table('ctickets')
            ->leftJoin('employees', 'ctickets.employeeid', '=', 'employees.employeeid')
            ->whereDate('ctickets.cticketdate', $date)
            ->when($siteId, fn($q) => $q->where('ctickets.siteid', $siteId))
            ->select(
                'employees.nom as vendeur',
                DB::raw('SUM(ctickets.totalttc) as montant')
            )
            ->groupBy('employees.nom')
            ->get();

        // 4. Caisse et Recette (Journal)
        $etatJourneeJournal = DB::table('journalcaisses')
            ->whereDate('dateouverture', $date)
            ->when($siteId, fn($q) => $q->where('siteid', $siteId))
            ->first();



        // État de journée : sessions caisse du jour
        $journeeSessions = DB::table('journalcaisses')
            ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
            ->leftJoin('sites', 'journalcaisses.siteid', '=', 'sites.siteid')
            ->whereDate('journalcaisses.dateouverture', $date)
            ->select(
                'journalcaisses.journalcaisseid',
                'journalcaisses.dateouverture',
                'journalcaisses.datecloture',
                'journalcaisses.fondcaisse',
                'journalcaisses.montantcloture',
                'journalcaisses.montanttheorique',
                'journalcaisses.montantdepense',
                'journalcaisses.isclosed',
                'caisses.libelle as caisse_libelle',
                'sites.libelle as site_libelle'
            )
            ->orderBy('journalcaisses.dateouverture')
            ->get();

        $journeeTotaux = (object) [
            'nombre_sessions' => $journeeSessions->count(),
            'total_fondcaisse' => $journeeSessions->sum('fondcaisse'),
            'total_cloture' => $journeeSessions->sum('montantcloture'),
            'total_theorique' => $journeeSessions->sum('montanttheorique'),
            'total_depense' => $journeeSessions->sum('montantdepense'),
        ];

        // Journal caisse : détail d'une session ou lignes du jour
        $journalLignes = collect();
        $journalEntete = null;
        if ($journalId) {
            $journalEntete = DB::table('journalcaisses')
                ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
                ->leftJoin('sites', 'journalcaisses.siteid', '=', 'sites.siteid')
                ->where('journalcaisses.journalcaisseid', $journalId)
                ->select(
                    'journalcaisses.*',
                    'caisses.libelle as caisse_libelle',
                    'sites.libelle as site_libelle'
                )
                ->first();
            $journalLignes = DB::table('journalcaissedets')
                ->leftJoin('sectionclotures', 'journalcaissedets.sectionclotureid', '=', 'sectionclotures.sectionclotureid')
                ->where('journalcaissedets.journalcaisseid', $journalId)
                ->select(
                    'journalcaissedets.*',
                    'sectionclotures.libelle as section_libelle'
                )
                ->orderBy('journalcaissedets.priorite')
                ->orderBy('journalcaissedets.journalcaissedetid')
                ->get();
        } else {
            $idsJour = $journeeSessions->pluck('journalcaisseid')->toArray();
            if (!empty($idsJour)) {
                $journalLignes = DB::table('journalcaissedets')
                    ->leftJoin('sectionclotures', 'journalcaissedets.sectionclotureid', '=', 'sectionclotures.sectionclotureid')
                    ->leftJoin('journalcaisses', 'journalcaissedets.journalcaisseid', '=', 'journalcaisses.journalcaisseid')
                    ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
                    ->whereIn('journalcaissedets.journalcaisseid', $idsJour)
                    ->select(
                        'journalcaissedets.*',
                        'sectionclotures.libelle as section_libelle',
                        'caisses.libelle as caisse_libelle',
                        'journalcaisses.dateouverture'
                    )
                    ->orderBy('journalcaisses.dateouverture')
                    ->orderBy('journalcaissedets.priorite')
                    ->get();
            }
        }

        // Rapport mensuel : agrégat par mois (Plage de dates)
        $start = \Carbon\Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
        $end = \Carbon\Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth();
        
        $rapportMensuelDetail = [];
        $current = clone $start;

        while ($current->lte($end)) {
            $mStart = $current->copy()->startOfMonth();
            $mEnd = $current->copy()->endOfMonth();
            $monthLabel = $current->format('m-Y');

            $vente = DB::table('cfactures')
                ->whereBetween('cfacturedate', [$mStart, $mEnd])
                ->when($siteId, fn($q) => $q->where('siteid', $siteId))
                ->sum('netapayer');

            $achat = DB::table('ffactures')
                ->whereBetween('ffacturedate', [$mStart, $mEnd])
                ->when($siteId, fn($q) => $q->where('siteid', $siteId))
                ->sum('netapayer');

            $reg_clt = DB::table('creglements')
                ->whereBetween('date', [$mStart, $mEnd])
                ->when($siteId, fn($q) => $q->where('siteid', $siteId))
                ->sum('montant');

            $reg_frs = DB::table('freglements')
                ->whereBetween('date', [$mStart, $mEnd])
                ->when($siteId, fn($q) => $q->where('siteid', $siteId))
                ->sum('montant');

            $depense = DB::table('depenses')
                ->whereBetween('date', [$mStart, $mEnd])
                ->when($siteId, fn($q) => $q->where('siteid', $siteId))
                ->sum('montant');

            $resultat = $reg_clt - $reg_frs - $depense;

            $rapportMensuelDetail[] = [
                'date' => $monthLabel,
                'vente' => $vente,
                'achat' => $achat,
                'reglement_clt' => $reg_clt,
                'reglement_frs' => $reg_frs,
                'depense' => $depense,
                'resultat' => $resultat
            ];

            $current->addMonth();
        }

        // Keep existing aggregates for simple month view if needed (optional, or just use detail[0])
        $rapportMensuel = count($rapportMensuelDetail) > 0 ? (object)$rapportMensuelDetail[0] : null;

        // Journal de caisse détaillé (Plage de dates)
        $sessionsJournal = DB::table('journalcaisses')
            ->leftJoin('caisses', 'journalcaisses.caisseid', '=', 'caisses.caisseid')
            ->leftJoin('sites', 'journalcaisses.siteid', '=', 'sites.siteid')
            ->leftJoin('employees', 'journalcaisses.employeeid', '=', 'employees.employeeid')
            ->whereBetween('journalcaisses.dateouverture', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->when($siteId, fn($q) => $q->where('journalcaisses.siteid', $siteId))
            ->when($etat !== 'Tous', function($q) use ($etat) {
                return $q->where('journalcaisses.isclosed', $etat === 'Clôturé');
            })
            ->select(
                'journalcaisses.journalcaisseid',
                'sites.siteabrege as site_libelle',
                'journalcaisses.dateouverture',
                'journalcaisses.datecloture',
                'caisses.libelle as agence_pv',
                'journalcaisses.fondcaisse',
                'journalcaisses.recettebrut',
                'journalcaisses.totalespece as especes',
                'journalcaisses.totalcheque as cheques',
                'journalcaisses.totaltpe as tpe',
                'journalcaisses.totalbonconvention as bon_conv',
                'journalcaisses.totalregavoir as avoir_repris',
                'journalcaisses.totalregautre as autre',
                'journalcaisses.montantdepense as depenses',
                'journalcaisses.recettenet',
                'journalcaisses.acomptenewticket as acomptes',
                'journalcaisses.totalcreditacompte as credit_acompte',
                'journalcaisses.complementacompte as comp_acompte',
                'journalcaisses.totalacompteticket as tot_acomptes',
                'journalcaisses.montantcloture',
                'journalcaisses.isclosed',
                'employees.nom as caissier_nom',
                'employees.prenom as caissier_prenom',
                'journalcaisses.siteid'
            )
            ->orderBy('journalcaisses.dateouverture', 'desc')
            ->get();

        return Inertia::render('Statistics', [
            'date' => $date,
            'from_month' => $fromMonth,
            'to_month' => $toMonth,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'etat' => $etat,
            'site_id' => $siteId,
            'tab' => $tab,
            'journal_id' => $journalId,
            'sessionsJournal' => $sessionsJournal,
            'journeeSessions' => $journeeSessions,
            'journeeTotaux' => $journeeTotaux,
            'journalLignes' => $journalLignes,
            'journalEntete' => $journalEntete,
            'rapportMensuel' => $rapportMensuel,
            'rapportMensuelDetail' => array_reverse($rapportMensuelDetail), // Newest first
            'sites' => DB::table('sites')->select('siteid', 'libelle')->orderBy('libelle')->get(),
            'ventesRubrique' => $ventesRubrique,
            'chiffreAffaireFamille' => $chiffreAffaireFamille,
            'chiffreAffaireVendeur' => $chiffreAffaireVendeur,
            'etatJourneeJournal' => $etatJourneeJournal,
            'caisses' => DB::table('caisses')->select('caisseid', 'libelle')->orderBy('libelle')->get(),

        ]);

    }
}
