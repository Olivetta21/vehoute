<template>
        <HeaderTelas
            :titulo="PagesRoutes.all.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            @voltar="this.$router.back()"
            @pesquisa="filterConfig"
        />

        <div class="tabela table-page">
            <p class="table-summary">Configurações do usuário</p>
            <div class="table-toolbar">
            <button :disabled="!configsChanged"
                @click="salvarTodos()">
                Salvar Alterações
            </button>
            <button class="btn-secondary" :disabled="!configsChanged"
                @click="cancelarAlteracoes()">
                Cancelar
            </button>
            </div>

            <div class="table-wrap">
            <table class="data-table">
                <tbody>
                    <template v-for="(classe, index) in configs" :key="index">
                        <tr class="group-row">
                            <td colspan="2">{{ classe.classe_nome }}</td>
                        </tr>
                        <tr v-for="(config, index) in classe.configs" :key="index">
                            <td class="config-nome">
                                {{ config.config_nome }}
                            </td>
                            <td>
                                <SwitchButtonInput v-if="config.config_var_type === 'boolean'"
                                    :value="config.v_model_variavel"
                                    trueValue="true"
                                    falseValue="false"
                                    @update:value="config.v_model_variavel = $event"
                                />
                                <input v-else v-model="config.v_model_variavel" />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
</template>

<script>
import { compararVariaveis } from '../../scripts/utils';
import Config from '../../scripts/ConfigPage/Config';
import PagesRoutes from '../../scripts/PagesRoutes';
import HeaderTelas from '../utils/HeaderTelas.vue';
import SwitchButtonInput from '../utils/SwitchButtonInput.vue';

export default {
    name: 'ConfigPage',
    data() {
        return {
            PagesRoutes,
            Config,
            compararVariaveis,

            fetched_configs: [],
            configs: [],

            last_name_filter: ''
        };
    },
    methods: {
        filterConfig(name) {
            this.last_name_filter = name || '';
            const normalizedName = this.last_name_filter.trim().toLowerCase();

            if (!normalizedName) {
                this.configs = this.fetched_configs;
                return;
            }

            this.configs = this.fetched_configs
                .map(classe => ({
                    ...classe,
                    configs: classe.configs.filter(config =>
                        config.config_nome.toLowerCase().includes(normalizedName)
                    )
                }))
                .filter(classe => classe.configs.length > 0);
        },
        setOrUpdateConfigs(configs) {
            if  (configs && Array.isArray(configs)) { //set
                configs.forEach(classe => {
                    classe.configs.forEach(config => {
                        config.v_model_variavel = config.config_variavel;
                    });
                })
                
                this.fetched_configs = configs;
            }

            else if (configs && typeof configs === 'object' && configs.config_id) { //update
                let c = null;

                for (let classe of this.fetched_configs) {
                    for (let config of classe.configs) {
                        if (config.config_id === configs.config_id) {
                            c = classe;
                            config.config_variavel = configs.variavel;
                            config.v_model_variavel = configs.variavel;
                            break;
                        }
                    }
                    if (c) break;
                }
            }

            this.filterConfig(this.last_name_filter);
        },
        async fetchConfig() {
            let c = await Config.getConfigs();
            this.setOrUpdateConfigs(c);
        },
        async atualizarConfigUsuario(config) {
            let result = await Config.atualizarConfigUsuario(config.config_id, config.v_model_variavel);
            if (result) {
                this.setOrUpdateConfigs(result);
            }
        },
        salvarTodos() {
            for (let classe of this.configs) {
                for (let config of classe.configs) {
                    if (compararVariaveis(config.v_model_variavel, config.config_variavel) !== 0) {
                        this.atualizarConfigUsuario(config);
                    }
                }
            }
        },
        cancelarAlteracoes() {
            for (let classe of this.configs) {
                for (let config of classe.configs) {
                    config.v_model_variavel = config.config_variavel;
                }
            }
        }
    },
    components: {
        HeaderTelas,
        SwitchButtonInput
    },
    mounted() {
        this.fetchConfig();
    },
    computed: {
        configsChanged() {
            for (let classe of this.configs) {
                for (let config of classe.configs) {
                    if (compararVariaveis(config.v_model_variavel, config.config_variavel) !== 0) {
                        return true;
                    }
                }
            }
            return false;
        }
    }
};
</script>

<style scoped>
.tabela { width: 100%; max-width: 760px; }
.config-nome { width: 60%; }
.tabela input { width: 100%; min-width: 4rem; }
</style>
