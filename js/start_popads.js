document.getElementById('start_popads').addEventListener('click', function() {
    fetch('start_popads.php')
        .then(response => response.text())
        .then(data => alert(data))
        .catch(error => console.error(error));
});
