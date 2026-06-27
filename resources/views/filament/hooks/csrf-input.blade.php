<script>
// Add hidden CSRF token input to login form for native form submission fallback
(function() {
    var form = document.querySelector('form');
    if (form && !form.querySelector('input[name="_token"]')) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = '{{ csrf_token() }}';
        input.autocomplete = 'off';
        form.appendChild(input);
    }
})();
</script>
