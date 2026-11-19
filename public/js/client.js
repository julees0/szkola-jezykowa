document.addEventListener('submit', function (zdarzenie) {
    var pytanie = zdarzenie.target.getAttribute('data-potwierdz');
    if (pytanie && !window.confirm(pytanie)) {
        zdarzenie.preventDefault();
    }
});
