<template>
    <div>
        <p v-if="placeholder">{{ placeholder }}</p>
        <div class="select-input-container">
            <template v-if="searchFn">
                <input v-if="is_searching" type="text" v-model="searchText" :placeholder="searchPlaceholder" @input="search" class="search-input"/>
                <button v-else @click="startSearch" class="search-bttn"> {{ searchBttnText }} </button>
            </template>
            <select @change="handleChange" class="select-input" v-model="selected_value" :required="required">
                <option value=""> {{ !real_data || real_data?.length < 1 ? 'Nenhuma opção encontrada' : 'Selecione' }} </option>
                <option v-for="item in real_data" :key="item.id" :value="item.id">
                    {{ item.nome }}
                </option>
            </select>
        </div>
    </div>
</template>


<script>
export default {
    name: 'SelectInput',
    props: {
        required: {
            type: Boolean,
            required: false,
            default: true
        },
        selectedId: {
            type: Number,
            required: false,
            default: null
        },
        preSelectedValue: {
            type: Object,
            required: false,
            default: null
        },
        data: {
            /* Exemplo:
            {
                {
                    "id": 1,
                    "nome": "Nome 1"
                },
                {
                    "id": 2,
                    "nome": "Nome 2"
                }
            }
            */
            type: Object,
            required: false,
            default: () => {}
        },
        placeholder: {
            type: String,
            required: false
        },
        searchPlaceholder: {
            type: String,
            required: false,
            default: 'Pesquisar'
        },
        searchBttnText: {
            type: String,
            required: false,
            default: 'Pesquisar'
        },
        searchFn: {
            type: Function,
            required: false
        },
    },
    emits: ['update:value'],
    data() {
        return {
            is_searching: false,
            searchText: '',
            selected_value: "",
            timeoutStopSearch: null,
            timeoutPesquisa: null,
            real_data: null
        };
    },
    mounted() {
        this.updateRealData(this.data || []);
        if (this.preSelectedValue) {
            this.updateRealData([this.preSelectedValue]);
            this.selected_value = this.preSelectedValue.id;
            this.$emit('update:value', this.preSelectedValue);
        }
        if (this.selectedId) {
            const selectedItem = this.real_data.find(item => item.id === this.selectedId);
            if (selectedItem) {
                this.selected_value = selectedItem.id;
                this.$emit('update:value', selectedItem);
            }
        }
    },
    methods: {
        handleChange(event) {
            this.searchText = '';
            this.is_searching = false;
            const id = Number(event.target.value);
            this.$emit('update:value', this.real_data.find(item => item.id === id));
        },
        updateRealData(newData) {
            this.real_data = newData;
            this.$emit('update:value', "");
        },
        stopSearch() {
            this.is_searching = false;
            this.searchText = '';
        },
        startSearch() {
            this.is_searching = true;
            this.updateRealData([]);
            this.selected_value = "";
        },
        async search() {
            if (this.searchFn) {
                this.updateRealData([]);
                this.selected_value = "";

                clearTimeout(this.timeoutStopSearch);
                this.timeoutStopSearch = setTimeout(async () => {
                    this.stopSearch();
                }, 5000);

                if (this.searchText.length < 3) {
                    clearTimeout(this.timeoutPesquisa);
                    return;
                }

                clearTimeout(this.timeoutPesquisa);

                this.timeoutPesquisa = setTimeout(async () => {
                    this.updateRealData(await this.searchFn(this.searchText));
                }, 500);
            }
        }
    }
}
</script>

<style scoped>
.select-input-container {
    display: flex;
    gap: 0;
    align-items: center;
}

.select-input, .search-bttn, .search-input {
    padding: 0;
    border: 1px solid var(--colorC3);
    border-radius: 0;
    height: 1.5rem;
}
</style>