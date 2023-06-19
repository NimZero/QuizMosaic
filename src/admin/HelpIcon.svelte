<script>
    import { onMount } from "svelte";

    export let visible = false;
    export let popover = null;

    function togglePopover() {
        visible = !visible;
    }

    onMount(() => {
        document.addEventListener("click", handleOutsideClick);
    });

    function handleOutsideClick(event) {
        const target = event.target;

        if (popover && !popover.contains(target)) {
            visible = false;
        }
    }
</script>

<div id="help-popover" class="container" bind:this={popover}>
    <button type="button" class="help-icon" on:click={togglePopover}>?</button>
    {#if visible}
        <div class="popover">
            <slot />
        </div>
    {/if}
</div>
