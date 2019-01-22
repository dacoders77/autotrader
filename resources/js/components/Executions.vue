<template>
    <div class="container" >
        <div class="row mt-3">
            <div style="width: 100%">
                <div class="card">
                    <div class="card-header"><span style="font-size:140%">Executions</span>
                        <div class="card-tools">
                            <!-- Button trigger modal -->
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th><i class="fas fa-info-circle blue"></i></th>
                                    <th>Created</th>
                                    <th>Signal id</th>
                                    <th>Client id</th>

                                    <th>Client name</th>
                                    <th>Symbol</th>
                                    <th>Multiplier</th>

                                    <th>Direction</th>
                                    <th>Client volume</th>
                                    <th>Percent</th>
                                    <th>Leverage</th>

                                    <th>Client funds</th>
                                    <th>Status</th>
                                    <th>Open status</th>
                                    <th>Close status</th>

                                    <th>Open price</th>
                                    <th>Close price</th>
                                    <th>Open response</th>
                                    <th>Close response</th>

                                    <th>Info</th>

                                </tr>
                                <tr v-for="signal in clients.data" :key="signal.id">
                                    <td>{{ signal.id }}</td>
                                    <td>{{ signal.created_at | myDate }}</td>
                                    <td>{{ signal.signal_id }}</td>
                                    <td>{{ signal.client_id }}</td>

                                    <td>{{ signal.client_name }}</td>
                                    <td>{{ signal.symbol }}</td>
                                    <td>{{ signal.multiplier }}</td>

                                    <td>{{ signal.direction }}</td>
                                    <td>{{ signal.client_volume }}</td>
                                    <td>{{ signal.percent }}</td>
                                    <td>{{ signal.leverage }}</td>

                                    <td>{{ signal.client_funds }}</td>
                                    <td>{{ signal.status }}</td>
                                    <td>{{ signal.open_status }}</td>
                                    <td>{{ signal.close_status }}</td>

                                    <td>{{ signal.open_price }}</td>
                                    <td>{{ signal.close_price }}</td>
                                    <td>{{ signal.open_response }}</td>
                                    <td>{{ signal.close_response }}</td>

                                    <td>{{ signal.info }}</td>
                                </tr>
                                </tbody></table>
                        </div>
                        <!-- /.card-body -->
                        <!-- Pagination -->
                        <div class="card-footer">

                            <ul class="pagination justify-content-center">
                                <pagination :data="clients" @pagination-change-page="getResults"></pagination>
                            </ul>


                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>
</template>

<script>
    export default {
        data() {
            return {
                editmode: false, // Modal edit record or create new flag
                clients: {},
                form: new Form({ // Class instance
                    id: '',
                    name: '',
                    last_name: '',
                    telegram: '',
                    email: '',
                    api: '',
                    api_secret: '',
                    info: ''
                })
            }
        },
        methods: {
            // Pagination. https://github.com/gilbitron/laravel-vue-pagination
            getResults(page = 1) {
                axios.get('api/execution?page=' + page)
                    .then(response => {
                        this.clients = response.data;
                    });
            },
            loadClients() {
                axios.get('api/execution').then(({data}) => (this.clients = data)); // Resource controllers are defined in api.php
            }
        },
        created() {
            this.loadClients();

            //Event listener
            Fire.$on('AfterCreate', () => {
                this.loadClients();
            });

        }
    }
</script>
