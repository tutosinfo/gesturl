document.getElementById('stop_popads').addEventListener('click', function() {
    fetch('stop_popads.php')
        .then(response => response.text())
        .then(data => alert(data))
        .catch(error => console.error(error));
});
