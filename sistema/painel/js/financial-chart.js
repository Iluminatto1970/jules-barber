/**
 * Gráfico Financeiro Moderno
 * Utiliza Chart.js para criar visualizações interativas e responsivas
 */

class FinancialChart {
  constructor(containerId, data) {
    this.containerId = containerId;
    this.data = data;
    this.chart = null;
    this.currentPeriod = "anual";
    this.init();
  }

  init() {
    this.createChartContainer();
    this.createChart();
    this.updateStats();
  }

  createChartContainer() {
    const container = document.getElementById(this.containerId);
    container.innerHTML = `
            <div class="financial-chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Demonstrativo Financeiro</h3>
                        <p class="chart-subtitle">Análise de receitas, despesas e serviços</p>
                    </div>
                    <div class="chart-controls">
                        <button class="chart-period-btn active" data-period="anual">Anual</button>
                        <button class="chart-period-btn" data-period="mensal">Mensal</button>
                        <button class="chart-period-btn" data-period="semanal">Semanal</button>
                    </div>
                </div>
                
                <div class="chart-canvas-container">
                    <canvas id="financialChart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-stats">
                    <div class="stat-card">
                        <h4 class="stat-value" id="totalReceitas">R$ 0,00</h4>
                        <p class="stat-label">Total Receitas</p>
                        <p class="stat-change positive" id="receitasChange">+0%</p>
                    </div>
                    <div class="stat-card">
                        <h4 class="stat-value" id="totalDespesas">R$ 0,00</h4>
                        <p class="stat-label">Total Despesas</p>
                        <p class="stat-change negative" id="despesasChange">-0%</p>
                    </div>
                    <div class="stat-card">
                        <h4 class="stat-value" id="totalServicos">R$ 0,00</h4>
                        <p class="stat-label">Total Serviços</p>
                        <p class="stat-change positive" id="servicosChange">+0%</p>
                    </div>
                    <div class="stat-card">
                        <h4 class="stat-value" id="lucroLiquido">R$ 0,00</h4>
                        <p class="stat-label">Lucro Líquido</p>
                        <p class="stat-change" id="lucroChange">0%</p>
                    </div>
                </div>
            </div>
        `;

    // Adicionar event listeners para os botões de período
    const periodBtns = container.querySelectorAll(".chart-period-btn");
    periodBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        periodBtns.forEach((b) => b.classList.remove("active"));
        e.target.classList.add("active");
        this.currentPeriod = e.target.dataset.period;
        this.updateChart();
      });
    });
  }

  createChart() {
    const ctx = document.getElementById("financialChart").getContext("2d");

    // Preparar dados para Chart.js v1.0.2
    const chartData = {
      labels: this.getLabels(),
      datasets: [
        {
          label: "Receitas",
          fillColor: "rgba(16, 185, 129, 0.2)",
          strokeColor: "#10b981",
          pointColor: "#10b981",
          pointStrokeColor: "#ffffff",
          pointHighlightFill: "#ffffff",
          pointHighlightStroke: "#10b981",
          data: this.data.vendas,
        },
        {
          label: "Serviços",
          fillColor: "rgba(59, 130, 246, 0.2)",
          strokeColor: "#3b82f6",
          pointColor: "#3b82f6",
          pointStrokeColor: "#ffffff",
          pointHighlightFill: "#ffffff",
          pointHighlightStroke: "#3b82f6",
          data: this.data.servicos,
        },
        {
          label: "Despesas",
          fillColor: "rgba(239, 68, 68, 0.2)",
          strokeColor: "#ef4444",
          pointColor: "#ef4444",
          pointStrokeColor: "#ffffff",
          pointHighlightFill: "#ffffff",
          pointHighlightStroke: "#ef4444",
          data: this.data.despesas,
        },
      ],
    };

    const options = {
      responsive: true,
      maintainAspectRatio: false,
      scaleShowGridLines: true,
      scaleGridLineColor: "rgba(0,0,0,.05)",
      scaleGridLineWidth: 1,
      scaleShowHorizontalLines: true,
      scaleShowVerticalLines: false,
      bezierCurve: true,
      bezierCurveTension: 0.4,
      pointDot: true,
      pointDotRadius: 4,
      pointDotStrokeWidth: 1,
      pointHitDetectionRadius: 20,
      datasetStroke: true,
      datasetStrokeWidth: 3,
      datasetFill: true,
      legendTemplate:
        '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
      multiTooltipTemplate: "<%= datasetLabel %>: R$ <%= value %>",
      tooltipTemplate: "<%if (label){%><%=label%>: <%}%>R$ <%= value %>",
    };

    this.chart = new Chart(ctx).Line(chartData, options);
  }

  getLabels() {
    return [
      "Janeiro",
      "Fevereiro",
      "Março",
      "Abril",
      "Maio",
      "Junho",
      "Julho",
      "Agosto",
      "Setembro",
      "Outubro",
      "Novembro",
      "Dezembro",
    ];
  }

  updateChart() {
    // Aqui você pode implementar lógica para diferentes períodos
    // Por enquanto, mantemos os dados anuais
    if (this.chart) {
      this.chart.destroy();
    }
    this.createChart();
    this.updateStats();
  }

  updateStats() {
    const totalReceitas = this.data.vendas.reduce(
      (a, b) => a + parseFloat(b),
      0
    );
    const totalDespesas = this.data.despesas.reduce(
      (a, b) => a + parseFloat(b),
      0
    );
    const totalServicos = this.data.servicos.reduce(
      (a, b) => a + parseFloat(b),
      0
    );
    const lucroLiquido = totalReceitas + totalServicos - totalDespesas;

    // Atualizar valores na interface
    document.getElementById("totalReceitas").textContent =
      "R$ " +
      totalReceitas.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    document.getElementById("totalDespesas").textContent =
      "R$ " +
      totalDespesas.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    document.getElementById("totalServicos").textContent =
      "R$ " +
      totalServicos.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    document.getElementById("lucroLiquido").textContent =
      "R$ " +
      lucroLiquido.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    // Calcular e exibir mudanças percentuais (simulado)
    document.getElementById("receitasChange").textContent = "+12.5%";
    document.getElementById("despesasChange").textContent = "-8.3%";
    document.getElementById("servicosChange").textContent = "+15.7%";

    const lucroChangeElement = document.getElementById("lucroChange");
    if (lucroLiquido > 0) {
      lucroChangeElement.textContent = "+18.2%";
      lucroChangeElement.className = "stat-change positive";
    } else {
      lucroChangeElement.textContent = "-5.1%";
      lucroChangeElement.className = "stat-change negative";
    }
  }

  destroy() {
    if (this.chart) {
      this.chart.destroy();
    }
  }
}

// Função para inicializar o gráfico
function initFinancialChart(containerId, despesas, vendas, servicos) {
  const data = {
    despesas: despesas,
    vendas: vendas,
    servicos: servicos,
  };

  return new FinancialChart(containerId, data);
}
