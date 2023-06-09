import './plugin.scss'
import PluginApp from './PluginApp.svelte'

wp.api.loadPromise.done( function() {
  const app = new PluginApp({
    target: document.getElementById('app'),
  });
});

export default app
