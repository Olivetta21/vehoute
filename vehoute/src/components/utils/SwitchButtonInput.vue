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
                newValue = this.trueValue;
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
.switch-button-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}

.switch-button-label {
    font-size: 0.875rem;
    color: var(--colorC2, #2d3748);
}

.switch-button {
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.switch-button.disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.switch-button-track {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 2.7rem;
    height: 1.5rem;
    padding: 0.125rem;
    border-radius: 999px;
    background-color: var(--colorC6, #d0d7de);
    transition: background-color 0.2s ease;
    box-sizing: border-box;
}

.switch-button.active .switch-button-track {
    background-color: var(--colorPrimary, #1f6feb);
}

.switch-button.inactive .switch-button-track {
    background-color: var(--colorDanger, #dc3545);
}

.switch-button-thumb {
    position: absolute;
    left: 0.125rem;
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 50%;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease;
}

.switch-button.active .switch-button-thumb {
    transform: translateX(1.15rem);
}

.switch-button.neutral .switch-button-thumb {
    transform: translateX(0.65rem);
}

.switch-button-checkbox-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
