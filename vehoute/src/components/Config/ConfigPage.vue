<template>
        <HeaderTelas
            :titulo="PagesRoutes.find(r => r.name === this.$route.name)?.pageName"
            :mostrarVoltar="true"
            :mostrarPesquisa="true"
            @voltar="this.$router.back()"
            @pesquisa="filterConfig"
        />

        <div class="tabela">
            <p class="tabela-resumo">Configurações do usuário</p>
            <button :disabled="!configsChanged"
                @click="salvarTodos()">
                Salvar Alterações
            </button>
            <button :disabled="!configsChanged"
                @click="cancelarAlteracoes()">
                Cancelar
            </button>

            <table>
                <tbody>
                    <template v-for="(classe, index) in configs" :key="index">
                        <tr class="agrupamento-titulo">
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
.tabela {
    padding: 12px;
    overflow-x: auto;
}

.tabela-resumo {
    margin-bottom: 10px;
    color: #666;
    font-size: 0.95rem;
}

table {
    border-collapse: collapse;
    color: #333;
}

th,
td {
    padding: 9px 12px;
    text-align: left;
}

th {
    background-color: #f5f5f5;
    color: #555;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}

th:last-child,
td:last-child {
    white-space: nowrap;
}

.agrupamento-titulo {
    border-bottom: 2px solid #ddd;
    font-weight: bold;
}

.agrupamento-perms {
    color: #555;
}

.config-nome {
    text-align: right;
}

button {
    min-width: 32px;
    padding: 5px 8px;
    font-size: 14px;
    line-height: 1;
}
</style>