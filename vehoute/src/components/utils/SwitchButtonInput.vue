<template>
    <div class="switch-button-wrapper">
        <label v-if="label" class="switch-button-label">{{ label }}</label>
        <button
            type="button"
            class="switch-button"
            :class="{
                active: is_active,
                inactive: is_inactive,
                neutral: is_neutral,
                disabled: disabled
            }"
            :disabled="disabled"
            :aria-pressed="is_active ? 'true' : is_inactive ? 'false' : 'mixed'"
            :aria-label="label || 'Alternar opção'"
            @click="toggle"
        >
            <span class="switch-button-track">
                <span class="switch-button-thumb"></span>
            </span>
        </button>
        <input
            v-if="required && !disabled && real_value == null"
            type="checkbox"
            class="switch-button-checkbox-hidden"
            required
            tabindex="-1"
            aria-hidden="true"
        />
    </div>
</template>

<script>
export default {
    name: 'SwitchButtonInput',
    props: {
        value: {
            type: [Boolean, Number, String, null],
            required: true
        },
        label: {
            type: String,
            required: false,
            default: ''
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        required: {
            type: Boolean,
            required: false,
            default: true
        },
        trueValue: {
            type: [Boolean, Number, String],
            required: false,
            default: true
        },
        falseValue: {
            type: [Boolean, Number, String],
            required: false,
            default: false
        },
        neutralValue: {
            type: [Boolean, Number, String, null],
            required: false,
            default: null
        },
        canSwitchToNeutral: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: ['update:value'],
    data() {
        return {
            real_value: null
        };
    },
    mounted() {
        this.real_value = this.value;
    },
    computed: {
        is_active() {
            return this.real_value === this.trueValue;
        },
        is_inactive() {
            return this.real_value === this.falseValue;
        },
        is_neutral() {
            return this.real_value === null;
        }
    },
    methods: {
        toggle() {
            if (this.disabled) {
                return;
            }

            let newValue;

            if (this.real_value === this.trueValue) {
                newValue = this.falseValue;
            } else {
                if (this.canSwitchToNeutral && this.real_value === this.falseValue) {
                    newValue = this.neutralValue;
                } else {
                    newValue = this.trueValue;
                }
            }

            this.$emit('update:value', newValue);
        }
    },
    watch: {
        value(newValue) {
            this.real_value = newValue;
        }
    }
}
</script>

<style scoped>
.switch-button-wrapper { display: inline-flex; align-items: center; gap: var(--space-2); min-height: var(--control-height-sm); }
.switch-button-label { color: var(--color-text); font-size: var(--font-sm); }

.switch-button,
.switch-button:hover,
.switch-button:disabled {
    min-height: 0;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: var(--radius-pill);
}
.switch-button.disabled { cursor: not-allowed; opacity: 0.55; }

.switch-button-track {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 2.5rem;
    height: 1.4rem;
    background: var(--color-border-strong);
    border-radius: var(--radius-pill);
    transition: background var(--transition);
}
.switch-button.active .switch-button-track { background: var(--color-primary); }
.switch-button.inactive .switch-button-track { background: var(--color-text-subtle); }

.switch-button-thumb {
    position: absolute;
    left: 0.15rem;
    width: 1.1rem;
    height: 1.1rem;
    background: var(--color-surface);
    border-radius: 50%;
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition);
}
.switch-button.active .switch-button-thumb { transform: translateX(1.1rem); }
.switch-button.neutral .switch-button-thumb { transform: translateX(0.55rem); }

.switch-button-checkbox-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
