Un indirizzo IPv4 è solo un numero di **32 bit**, diviso in quattro pezzi da 8 bit (gli ottetti). La subnet mask serve esclusivamente a dire **quanti bit appartengono alla rete** e **quanti agli host**. Tutto il resto sono conseguenze.

Quando vedi una maschera o un prefisso, la prima cosa da fare è **contare quanti bit sono di rete**. Se hai un prefisso /27, significa che **27 bit sono rete** e **32−27 = 5 bit sono host**. Non c’è interpretazione: è definizione pura.

Quei bit host rappresentano **tutte le combinazioni possibili** di 0 e 1. Con 5 bit ottieni 25=322^5 = 2 alla 5=32 indirizzi. Non sono tutti assegnabili: il primo (tutti 0) identifica la rete, l’ultimo (tutti 1) è il broadcast. Quindi gli host reali sono sempre 2 alla h−2. Se questo passaggio non ti è automatico, significa che non hai ancora interiorizzato il binario.

Ogni [[subnet]] è un **blocco continuo di indirizzi**. La dimensione del blocco è sempre una potenza di 2 ed è **esattamente** il numero di indirizzi totali della subnet. Per questo le subnet iniziano sempre a numeri “regolari”: 0, 32, 64, 96… perché 32 è un blocco, non un numero scelto a caso.

Per capire dove inizia una subnet, devi guardare **l’ottetto in cui la maschera non è 255**. Lì calcoli il blocco facendo 256 meno il valore della maschera in quell’ottetto. Con 224 il blocco è 32, con 240 è 16, con 192 è 64. Non è una formula magica: deriva dal fatto che 256 è il numero totale di combinazioni di un ottetto.

Una volta che conosci il blocco, la subnet di un IP è il **multiplo del blocco immediatamente inferiore** al valore dell’IP in quell’ottetto. Se il blocco è 32 e l’IP ha .63, la subnet è .32 perché 32 ≤ 63 < 64. Se sbagli qui, è perché stai contando “a occhio” invece che per intervalli.

L’indirizzo di rete è sempre il **primo indirizzo del blocco**. Il broadcast è sempre l’**ultimo indirizzo del blocco**. Gli host sono tutto ciò che sta in mezzo. Non devi “inventarli”: sono determinati automaticamente dal blocco.

Quando ti chiedono se due IP sono nella stessa subnet, la domanda reale è una sola: **cadono nello stesso blocco?** Se sì, stessa rete. Se no, reti diverse. Non c’entra il confronto diretto dei numeri, c’entra l’intervallo.

Quando ti danno un intervallo di IP e ti chiedono la subnet, devi verificare se la dimensione dell’intervallo è una **potenza di 2**. Se lo è, allora esiste un prefisso che lo rappresenta. Se non lo è, quell’intervallo **non può essere una singola subnet**. Questo è un controllo teorico che molti saltano e poi “forzano” il risultato.

Il numero di sottoreti nasce solo quando confronti una situazione di partenza con una nuova maschera. In ambito scolastico si parte dalla maschera base della classe (tipicamente /24 per classe C). Ogni bit che togli agli host e dai alla rete **raddoppia** il numero di subnet. Tre bit in più → 2^3 = 8 subnet. Non perché lo dice la formula, ma perché stai creando 8 combinazioni diverse di quei bit.

La verità scomoda è questa: il subnetting non è difficile, ma **non perdona il disordine mentale**. Se salti il concetto di blocco o confondi indirizzi totali con host utilizzabili, sbagli anche facendo i conti giusti. E se cerchi scorciatoie, le paghi tutte nell’esercizio successivo.