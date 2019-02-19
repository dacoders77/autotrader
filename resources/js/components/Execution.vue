<template>

    <div class="row pt-3">
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                        <span style="font-size:140%">

                            <div class="card-body table-responsive p-0">
                            <table class="table table-hover" style="width:100%">
                                <tbody>
                                <tr>
                                    <td class="px-2">
                                        Signal id: {{ (signal ? signal.id : null) }}<br>
                                        Symbol: {{ (signal ? signal.symbol : null) }}<br>
                                        Quote: {{ (signal ? signal.quote_value : null) }}<br>
                                        Quote status: {{ (signal ? signal.quote_status : null) }}<br>
                                    </td>
                                    <td>
                                        Perсent: {{ (signal ? signal.percent : null) }}<br>
                                        Leverage: {{ (signal ? signal.leverage : null) }}<br>
                                        Status: {{ (signal ? signal.status : null) }}<br>
                                        <!--Clients: {{ (signal ? Object.keys(signals.data).length : null) }}-->
                                    </td>

                                    <!-- START STOP BUTTONS WERE HERE!!! -->

                                    <td>
                                        <div class="card-tools text-right">
                                            <span v-for="(item, index) in items" :key="index">
                                                <a href="" @click.prevent="closeSymbol(signal, 'takeProfit' + index )" class="text-danger"><i class="fas fa-stop"></i></a>
                                                Out {{ index + 1 }}: {{ item }}%
                                                <br>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="card-tools text-right">
                                            <div class="btn-group">
                                                <div v-if="true">
                                                    <button class="btn btn-success" @click="executeSymbol(signal)"><i class="fas fa-play"></i></button>
                                                </div>
                                                <div v-if="true">
                                                    <button class="btn btn-danger" @click="closeSymbol(signal, 'stopLoss')"><i class="fas fa-stop"></i></button>
                                                </div>
                                                <div v-if="false">
                                                    <button class="btn btn-light" disabled><i class="fas fa-check"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </span>
                </div>

                <div class="card-body">

                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <tbody>

                            <template v-for="execution in signals">

                                <tr>
                                    <td>{{ execution.id }} <a href="" v-on:click.prevent="repeatExecution(execution)"><i class="fas fa-sync-alt"></i></a></td>
                                    <td>{{ execution.client_id }}</td>
                                    <td>{{ execution.client_name }}</td>

                                    <td>{{ execution.client_volume}}</td>
                                    <td>{{ execution.client_funds_value }}/{{ execution.client_funds_use }}</td>
                                    <td>
                                        <span v-if="execution.in_place_order_status == 'ok'" class="badge badge-pill badge-success">IN</span>
                                        <span v-if="execution.in_place_order_status == 'error'" class="badge badge-pill badge-danger">IN</span>
                                        <span v-if="execution.out_place_order_status == 'ok'" class="badge badge-pill badge-success">OUT</span>
                                        <span v-if="execution.out_place_order_status == 'error'" class="badge badge-pill badge-danger">OUT</span>
                                    </td>
                                </tr>

                                <tr class="detail-row">
                                    <td colspan="6">
                                        <div style="display: flex">
                                            <div style="border: 0px solid red">
                                                <span class="bg-success text-white">POS IN:</span><br>
                                                Calculate volume:
                                                <span v-if="signal.quote_value != null">ok</span>
                                                <span v-if="signal.quote_value == null">No quote!</span>
                                                <br>
                                                Get client funds: <a href="#" @click="newModal(execution.client_funds_response)">{{ execution.client_funds_status}}</a><br>
                                                Set leverage: <a href="#" @click="newModal(execution.leverage_response)">{{ execution.leverage_status}}</a><br>
                                                Place order: <a href="#" @click="newModal(execution.in_place_order_response)">{{ execution.in_place_order_status}}</a><br>
                                                Balance: <a href="#" @click="newModal(execution.in_balance_response)">{{ execution.in_balance_value}}</a><br>
                                            </div>
                                            <div>
                                                <!-- Four divs for exits -->
                                                <div v-for="(item, index) in items" :key="index" class="d-inline-block px-1" style="border: 0px solid red;">
                                                    <span class="bg-info text-white">Out {{ index + 1 }}: {{ item }}%</span><br>
                                                    Place order: <a href="#" @click="newModal(execution['out_response_' + (index + 1)])">{{ execution['out_status_' + (index + 1)]}}</a><br>
                                                    Balance: <a href="#" @click="newModal(execution['out_balance_response_' + (index + 1)])">{{ execution['out_balance_value_' + (index + 1)]}}</a><br>
                                                </div>
                                                <div class="px-1" style="border: 0px solid green">
                                                    <span class="bg-warning text-dark">Stop loss OUT:</span><br>
                                                    Place order: <a href="#" @click="newModal(execution.out_place_order_response)">{{ execution.out_place_order_status}}</a><br>
                                                    Balance: <a href="#" @click="newModal(execution.out_balance_response)">{{ execution.out_balance_value}}</a><br>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            </template>
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>
        </div>



        <div class="col-3">
            <div>
                <div class="card" style="width: 18rem;">
                    <div class="card-header">
                            <span style="font-size:140%">
                                Jobs: {{ jobsQuantity }}. Failed: {{ failedJobsQuantity }}
                                <button type="button" class="btn btn-success float-right" @click="clearJobTables">
                                <i class="far fa-trash-alt"></i>
                                </button>
                            </span>
                    </div>
                    <div class="card-body">
                        <span v-for="job in jobs">
                          <small>
                              {{ job.id }} - {{ job.displayName }} - {{ job.attempts }}<br>
                          </small>
                        </span>
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
                jsonModalMessage: [],
                jobs: null,
                failedJobsQuantity: 0,
                jobsQuantity: 0,
                items: [25, 25, 40, 10] // Temp list of exits. Remove it
            }
        },
        methods: {
            repeatExecution(signal) {
                //alert('repeat execution: ' + id); // Works good

                swal({
                    title: "You've got to be sure about that!?",
                    text: "Signal will be repeated only for selected client!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Hit the road, Jack!'
                }).then((result) => {
                    if (result.value) {
                        axios.post('repeatsignal', signal)
                            .then(response => {
                                swal(
                                    'Proceeded!',
                                    'Signal has been repeated',
                                    'success'
                                )
                                //Fire.$emit('AfterCreateSignal');
                                // even delete this signal?
                            })
                            .catch(error => {
                                swal("Failed!", "Error: \n" + error.response.data.message, "warning");
                                //Fire.$emit('AfterCreateSignal');
                            });
                    }
                })
            },
            clearJobTables() {
                axios.post('clearjobs')
                    .then(response => {
                        toast({
                            type: 'success',
                            title: 'Job tables truncated'
                        });
                    })
                    .catch(error => {
                        swal("Failed!", "Error: \n" + error.response.data.message, "warning");
                    });
            },
            newModal(message) {
                //this.editmode = false;
                //this.form.reset();
                this.jsonModalMessage = JSON.parse(message);
                $('#addNewSignalModal').modal('show');
            },
            closeSymbol(signal, exitType) {
                swal({
                    title: 'Are you sure?',
                    text: "Signal will be proceeded!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed it!'
                }).then((result) => {
                    if (result.value) {
                        axios.post('execclose', [signal, exitType])
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
            executeSymbol(signal) {
                swal({
                    title: 'Are you sure?',
                    text: "Signal will be proceeded!!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, proceed it!'
                }).then((result) => {
                    if (result.value) {
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
            loadUsers() {
                // Pagination disabled
                // TURN IT TO WEBSOCKET!
                axios.get('getexecution/' + this.signal.id).then(({data}) => {
                    this.signals = data['execution'];
                    this.signal = data['signal'][0];
                });
            },
            showError(error) {
            }
        },
        mounted: function () {

            this.interval = setInterval(function () {
                this.loadUsers();
            }.bind(this), 3000);

        },
        destroyed() {
            // Stop timer when closed
            clearInterval(this.interval);
        },
        created() {
            this.signal = this.$route.params.signal; // Props. Parameter. Sent from Signals.vue
            this.loadUsers();

            // Event listener
            Fire.$on('AfterCreateSignal', () => {
                this.loadUsers();
            });

            // Websocket listener
            // Sent from WebSocketStream.php
            Echo.channel('ATTR')
                .listen('AttrUpdateEvent', (e) => {
                    //this.jobs = e.update; //this.jobs = JSON.parse(e.update);
                    if (e.update.eventName === 'execution') {
                        //console.log(e.update.payLoad);
                        this.jobs = e.update.payLoad.jobsTable;
                        this.failedJobsQuantity = e.update.payLoad.failedJobsQuantity;
                        this.jobsQuantity = e.update.payLoad.jobsQuantity;
                    }

                });
        },
    }
</script>




