#!/usr/bin/perl

use Date::Format;
use WWW::Mechanize;
use HTML::TokeParser;
print "Deprecated, need to be updated to AN's new search engine\n";
exit(1);

$count = 0;
$legislature = shift || 14;
$loi = shift;
$debug = shift || 0;

@urls = ("http://recherche2.assemblee-nationale.fr/amendements/resultats.jsp?typeEcran=avance&chercherDateParNumero=non&NUM_INIT=".$loi."&NUM_AMEND=&AUTEUR=&DESIGNATION_ARTICLE=&DESIGNATION_ALINEA=&SORT_EN_SEANCE=&DELIBERATION=&NUM_PARTIE=&DateDebut=&DateFin=&periode=&LEGISLATURE=".$legislature."Amendements&QueryText=&Scope=TEXTEINTEGRAL&SortField=ORDRE_TEXTE&SortOrder=Asc&searchadvanced=Rechercher&ResultMaxDocs=10000&ResultCount=10000");

@organes = ("Affaires%20culturelles%20et%20%E9ducation",
            "Affaires%20%E9conomiques",
            "Affaires%20%E9trang%E8res",
            "Affaires%20sociales",
            "D%E9fense",
            "D%E9veloppement%20durable",
            "Lois",
            "Finances",
            "Toutes%20Commissions",
            "S%E9ance%20publique");
foreach $organe (@organes) {
  push(@urls, "http://recherche2.assemblee-nationale.fr/amendements/resultats.jsp?NUM_INIT=".$loi."&LEGISLATURE=".$legislature."&ORGANE=".$organe."&SortField=ORDRE_TEXTE&SortOrder=Asc&searchadvanced=Rechercher&ResultMaxDocs=10000&ResultCount=10000");
}

my %done;

foreach $url (@urls) {

if ($debug) {
    print "-> Download amendements from $url\n";
}
$a = WWW::Mechanize->new();
$a->get($url);
$content = $a->content;
$p = HTML::TokeParser->new(\$content);

while ($t = $p->get_tag('a')) {
    if ($t->[1]{class} eq 'lienamendement') {
	$htmfile = $t->[1]{href};
	next if ($htmfile =~ /(index|javascript)/);
    if ($done{$htmfile}) {
        if ($debug) {
            print "skip already dl\n";
        }
        next;
    }
    $done{$htmfile} = 1;
    $count++;
	$a->get($htmfile);
	$htmfile =~ s/^\s+//gi;
	$htmfile =~ s/\//_-_/gi;
	$htmfile =~ s/\#.*//;
	print "  $htmfile ... ";
	open FILE, ">:utf8", "html/$htmfile";
	print FILE $a->content;
	close FILE;
	print "downloaded.\n";
	$a->back();
    }
}

}
if ($debug) {
    print $count." amendements pour le projet de loi n°$loi\n";
}
