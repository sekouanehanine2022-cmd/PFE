window.DateApplication = Object.freeze({
    // Recupere la date locale du navigateur sans changer de jour a cause du fuseau horaire.
    aujourdhuiLocale: function () {
        const maintenant = new Date();
        const decalageLocal = maintenant.getTimezoneOffset() * 60000;

        return new Date(maintenant.getTime() - decalageLocal).toISOString().slice(0, 10);
    },
});
