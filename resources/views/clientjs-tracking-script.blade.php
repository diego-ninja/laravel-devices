<script>
    window.DeviceTracker = {
        config: @json([
            'current' => $current,
            'transport' => $transport,
            'library' => $library,
        ])
    };
</script>
<script src="{{ $library->url }}" integrity="sha512-jpobbeoWuk4rsYXs75ykhug4Guz41o8BNzlZvbtnLwVVdXxAoaMaPTHk1Oo1jF5u71PJ+luO/CFnzGPvFOHcIQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    if (window.DeviceTracker.config.current === null) {
        const promise = import(window.DeviceTracker.config.library.url)
            .then(ClientJS => new ClientJS())

        promise
            .then(fp => fp.get())
            .then(result => {
                const transport = window.DeviceTracker.config.transport

                window.DeviceTracker.config.current = result.visitorId
                document.cookie = `${transport.key}=${result.visitorId}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
            })
            .catch(error => console.error(error))
    }
</script>