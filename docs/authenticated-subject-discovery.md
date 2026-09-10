# Découvrabilité des sujets — espace authentifié

`public_is_listed` contrôle uniquement la présence d'un sujet dans les surfaces de découverte publique éventuelles. Il ne constitue pas une ACL et ne doit pas masquer un sujet dans le patrimoine authentifié à un utilisateur qui possède les droits nécessaires.

Les surfaces authentifiées appliquent :

```text
Subject::visibleTo($user)
+ status != archived
```

Cette règle couvre `/sujets`, `/sujets/arbre`, `/sujets/arbre-data`, `/documents/arbre` et `/documents/arbre-data`, ainsi que la recherche authentifiée.

Les documents sont ensuite filtrés par leur propre ACL (`SubjectDocument::visibleTo($user)`). Un `Subject` non archivé et autorisé reste donc visible même si `public_is_listed = false`, tandis que ses documents restent soumis à leurs niveaux `public`, `citizen` ou `working`.

La recherche guest conserve la restriction publique existante : `visibleTo(null)` et `listedInCatalogue()`, sans ouverture d'un catalogue interne. Les routes publiques et les valeurs éditoriales existantes ne sont pas modifiées.
