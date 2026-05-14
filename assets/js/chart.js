function loadBarChart(chartId, labels, data, labelName){

    const ctx = document.getElementById(chartId);

    new Chart(ctx, {

        type: 'bar',

        data: {

            labels: labels,

            datasets: [{

                label: labelName,

                data: data,

                borderWidth: 1

            }]

        },

        options: {

            responsive: true,

            scales: {

                y: {

                    beginAtZero: true

                }

            }

        }

    });

}