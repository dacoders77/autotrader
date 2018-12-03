<template>
    <div class="container">
        <div class="row mt-3">
            <div style="width: 100%">
                <div class="card">
                    <div class="card-header"><span style="font-size:140%">


                        <div class="container">
                          <div class="row">
                            <div class="col-sm">
                                Signal id: {{ signal.id }}<br>
                                Symbol: {{ signal.symbol }}<br>
                                Quote: {{ signal.quote_value }}<br>
                                Quote status: {{ signal.quote_status }}<br>
                            </div>
                            <div class="col-sm">
                                Perсent: {{ signal.percent }}<br>
                                Leverage: {{ signal.leverage }} <br>
                            </div>
                            <div class="col-sm">
                                Status: {{ signal.status }}<br>
                                Clients: {{ Object.keys(signals.data).length }}
                            </div>
                          </div>
                        </div>

                    </span>
                        <div class="card-tools">
                            <!-- Button right up corner -->
                            <div class="btn-group">
                                <div v-if="true">
                                    <button class="btn btn-success" @click="executeSymbol(signal)"><i class="fas fa-play"></i></button>
                                </div>
                                <div v-if="true">
                                    <button class="btn btn-danger" @click="closeSymbol(signal)"><i class="fas fa-stop"></i></button>
                                </div>
                                <div v-if="false">
                                    <button class="btn btn-light" disabled><i class="fas fa-check"></i></button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-body">
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th><i class="fas fa-info-circle blue"></i></th>
                                    <th>Client ID</th>
                                    <th>Name</th>
                                    <th>Volume</th>
                                    <th>Funds</th>
                                    <th>Result</th>
                                </tr>

                                <!--<tr v-for="signal in signals.data" :key="signal.id" :class="signal.status == 'finished' ? 'grey' : '' ">

                                    <td>{{ signal.id }}</td>
                                    <td>{{ signal.client_id }}</td>
                                    <td>{{ signal.client_name }}</td>

                                    <td>{{ signal.client_volume}}</td>
                                    <td>{{ signal.client_funds }}</td>
                                    <td>OK</td>

                                </tr>-->

                                <template v-for="execution in signals.data">
                                    <tr>
                                        <td>{{ execution.id }}</td>
                                        <td>{{ execution.client_id }}</td>
                                        <td>{{ execution.client_name }}</td>

                                        <td>{{ execution.client_volume}}</td>
                                        <td>{{ execution.client_funds_value }}</td>
                                        <td>
                                            <span v-if="execution.in_place_order_status == 'ok'" class="badge badge-pill badge-success">IN</span>
                                            <span v-if="execution.in_place_order_status == 'error'" class="badge badge-pill badge-danger">IN</span>
                                            <span v-if="execution.out_place_order_status == 'ok'" class="badge badge-pill badge-success">OUT</span>
                                            <span v-if="execution.out_place_order_status == 'error'" class="badge badge-pill badge-danger">OUT</span>

                                        </td>

                                    </tr>
                                    <tr class="detail-row">
                                        <td colspan="3">
                                            IN:<br>
                                            Calculate volume:
                                            <span v-if="signal.quote_value != null">ok</span>
                                            <span v-if="signal.quote_value == null">No quote!</span>
                                            <br>
                                            Get client funds: <a href="#" @click="showError(execution.client_funds_response)">{{ execution.client_funds_status }}</a><br>
                                            Set leverage: <a href="#" @click="showError(execution.leverage_response)">{{ execution.leverage_status }}</a><br>
                                            Place order: <a href="#" @click="showError(execution.in_place_order_response)">{{ execution.in_place_order_status }}</a><br>
                                            Balance: <a href="#" @click="showError(execution.in_balance_response)">{{ execution.in_balance_value }}</a><br>
                                            <!--
                                            Result:
                                            <span v-if="execution.in_status == 'success'" class="badge badge-pill badge-success">Success</span>
                                            <span v-if="execution.in_status == null" class="badge badge-pill badge-info">?</span>
                                            <span v-if="execution.in_status == 'pending'" class="badge badge-pill badge-secondary">Pending</span>
                                            <span v-if="execution.in_status == 'error'" class="badge badge-pill badge-danger">Error</span>-->

                                        </td>
                                        <td colspan="3">
                                            OUT:<br>
                                            Place order: <a href="#" @click="showError(execution.out_place_order_response)">{{ execution.out_place_order_status }}</a><br>
                                            Balance: <a href="#" @click="showError(execution.out_balance_response)">{{ execution.out_balance_value }}</a><br>
                                        </td>
                                    </tr>
                                </template>


                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                        <!-- Pagination -->
                        <div class="card-footer">

                            <!--<ul class="pagination justify-content-center">
                                <pagination :data="signals" @pagination-change-page="getResults"></pagination>
                            </ul>-->

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
                signal: {}, // Props. Sent from Signals.vue
                signals: {},
                interval: null,
            }
        },
        methods: {
            closeSymbol(signal){
                swal({
                    title: 'Are you sure?',
                    text: "Signal will be proceeded!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed it!'
                }).then((result) => {
                    if (result.value){
                        axios.post('execclose', signal)
                            .then(response => {
                                swal(
                                    'Proceeded!',
                                    'Signal has been proceeded',
                                    'success'
                                )
                                Fire.$emit('AfterCreateSignal');
                            })
                            .catch(error => {
                                swal("Failed!", "Error: \n" + error.response.data.message, "warning");
                                Fire.$emit('AfterCreateSignal');
                            });
                    }
                })
            },
            executeSymbol(signal){
                swal({
                    title: 'Are you sure?',
                    text: "Signal will be proceeded!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed it!'
                }).then((result) => {
                    if (result.value){
                        axios.post('exec', signal)
                            .then(response => {
                                swal(
                                    'Proceeded!',
                                    'Signal has been proceeded',
                                    'success'
                                )
                                Fire.$emit('AfterCreateSignal');
                            })
                            .catch(error => {
                                swal("Failed!", "Error: \n" + error.response.data.message, "warning");
                                Fire.$emit('AfterCreateSignal');
                            });
                    }
                })
            },
            loadUsers(){
                axios.get('getexecution/' + this.signal.id).then(({data}) => {
                    this.signals = data['execution']
                    this.signal = data['signal'][0]
                });
            },

/*            loadData: function () {
                alert('load data');
                axios.get('/api/data', function (response) {
                    //this.items = response.items;
                }.bind(this));
            },*/
            showError(error){
                swal({
                    type: 'info',
                    title: 'Bimex response: ',
                    text: error,
                    footer: '<a href>Why do I have this issue?</a>'
                })
            }
        },
        created() {
            // Props link
            this.signal = this.$route.params.signal; // Works good
            this.loadUsers();

            // Event listener
            Fire.$on('AfterCreateSignal', () => {
                this.loadUsers();
            });
        },
        mounted: function () {
            this.interval = setInterval(function () {
                this.loadUsers();
            }.bind(this), 3000);
        },
        destroyed(){
            // Stop timer when closed
            clearInterval(this.interval);
        }
    }
</script>
