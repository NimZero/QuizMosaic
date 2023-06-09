import './admin.scss'
import AdminApp from './AdminApp.svelte'

wp.api.loadPromise.done( function() {
    const app = new AdminApp({
        target: document.getElementById('app'),
    });
});

export default app
