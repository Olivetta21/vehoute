<template>
    <div id="toastcontainer" v-if="ToastMessage.toasts?.length > 0">
        <transition-group name="slide" tag="div">
            <div v-for="(toast) in ToastMessage.toasts" :key="toast.id" @click.stop="ToastMessage.removeById(toast.id)" class="toast-wrapper">
                <div :class="['toast', toast.type]">
                    <div class="textToast title"> {{ toast.title }} </div>
                    <div class="textToast message"> {{ toast.message }} </div>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<script>
import ToastMessage from '../../scripts/ToastMessage/ToastMessage';

export default {
	data() {
		return {
            ToastMessage
		};
	}
}
</script>

<style scoped>
#toastcontainer {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 1000;
    width: min(340px, 100%);
    max-height: 100%;
    padding: var(--space-3);
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-width: none;
    pointer-events: none;
}
.toast-wrapper { padding-bottom: var(--space-2); }
.toast {
    --toast-accent: var(--color-info);
    padding: var(--space-3) var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-left: 4px solid var(--toast-accent);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    cursor: pointer;
    pointer-events: all;
}
.toast:hover { background: var(--color-surface-muted); }
.toast.success { --toast-accent: var(--color-success); }
.toast.error { --toast-accent: var(--color-danger); }
.toast.warn { --toast-accent: var(--color-warning); }
.toast.info { --toast-accent: var(--color-info); }

.textToast { overflow: hidden; white-space: pre-line; overflow-wrap: anywhere; }
.textToast.title { color: var(--toast-accent); font-size: var(--font-xs); font-weight: var(--weight-bold); }
.textToast.message { color: var(--color-text); font-size: var(--font-sm); }

.slide-enter-active,
.slide-leave-active { transition: opacity var(--transition), transform var(--transition); }
.slide-enter-from,
.slide-leave-to { opacity: 0; transform: translateX(16px); }
</style>
