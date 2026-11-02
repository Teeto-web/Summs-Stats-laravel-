// Pendant le téléchargement des parties (file d'attente), on interroge le serveur
// toutes les 1,5 s et on remplace la liste, sans recharger toute la page.

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('match-list');
    const stats = document.getElementById('player-stats');

    // Rien à faire si aucun téléchargement n'est en cours
    if (!list || list.dataset.loading !== '1') {
        return;
    }

    const POLL_DELAY = 1500;  // millisecondes entre deux vérifications
    const MAX_ATTEMPTS = 60;  // on abandonne après 90 secondes
    let attempts = 0;

    async function poll() {
        attempts++;

        try {
            const response = await fetch(list.dataset.pollUrl, {
                headers: { 'Accept': 'application/json' },
            });

            if (response.ok) {
                const data = await response.json();
                list.innerHTML = data.html;

                if (stats) {
                    stats.innerHTML = data.statsHtml;
                }

                // Téléchargement terminé : on arrête
                if (!data.loading) {
                    return;
                }
            }
        } catch (error) {
            // Problème réseau passager : on réessaiera au prochain tour
        }

        if (attempts < MAX_ATTEMPTS) {
            setTimeout(poll, POLL_DELAY);
        }
    }

    setTimeout(poll, POLL_DELAY);
});
