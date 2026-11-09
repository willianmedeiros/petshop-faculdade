            </main>
        </div>
    </div>

    <!-- ========================================================
         MODAL DE AGENDAMENTO COMPARTILHADO (POPUP)
         ======================================================== -->
    <div id="modalAgendamento" class="modal-overlay" onclick="handleAgendamentoBackdropClick(event)">
        <div class="modal-card" style="max-width: 580px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modalAgendamentoTitle">
                    <span>📅</span> Novo Agendamento de Serviço
                </h3>
                <button type="button" class="modal-close-btn" onclick="fecharModalAgendamento()">✕</button>
            </div>

            <div class="modal-body">
                <form method="POST" action="agendamentos.php" id="formAgendamento">
                    <input type="hidden" name="action" id="agendamentoAction" value="cadastrar_agendamento">
                    <input type="hidden" name="agendamento_id" id="agendamentoId" value="">
                    <input type="hidden" name="servico" id="agendamentoServicoNome" value="">
                    <input type="hidden" name="valor" id="agendamentoValor" value="0.00">
                    <input type="hidden" name="status" id="agendamentoStatus" value="Agendado">

                    <!-- Seleção do Pet / Tutor (Em modo de edição mostra dados fixos do pet) -->
                    <div class="form-group" style="margin-bottom: 1.1rem;">
                        <label id="lblAgendamentoPet">Pet / Tutor: <span class="required">*</span></label>
                        <select name="pet_id" id="agendamentoPetId" class="form-control" onchange="atualizarObservacoesPetAoSelecionar()" required>
                            <option value="">-- Selecione o Pet --</option>
                            <?php foreach ($lista_pets_global as $p): ?>
                                <option value="<?= $p['pet_id'] ?>" data-nome="<?= htmlspecialchars($p['pet_nome']) ?>" data-tutor="<?= htmlspecialchars($p['tutor_nome']) ?>" data-especie="<?= htmlspecialchars($p['especie']) ?>" data-observacoes="<?= htmlspecialchars($p['observacoes'] ?? '') ?>">
                                    <?= htmlspecialchars($p['pet_nome']) ?> (<?= htmlspecialchars($p['especie']) ?><?= !empty($p['raca']) ? ' - ' . htmlspecialchars($p['raca']) : '' ?>) - Tutor: <?= htmlspecialchars($p['tutor_nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="infoPetEdicao" class="info-pet-card" style="display: none;">
                            <div class="info-pet-card-icon" id="infoPetEdicaoIcon">🐶</div>
                            <div class="info-pet-card-details">
                                <span class="info-pet-card-name" id="infoPetEdicaoNome">-</span>
                                <span class="info-pet-card-sub" id="infoPetEdicaoTutor">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Serviço: Valor atrelado automaticamente sem campo livre de edição -->
                    <div class="form-group" style="margin-bottom: 1.1rem;">
                        <label>Tipo de Serviço: <span class="required">*</span></label>
                        <select name="servico_id" id="agendamentoServicoId" class="form-control" onchange="atualizarPrecoServico()" required>
                            <option value="">-- Selecione o Serviço --</option>
                            <?php foreach ($lista_servicos_global as $s): ?>
                                <option value="<?= $s['id'] ?>" data-preco="<?= $s['preco'] ?>" data-nome="<?= htmlspecialchars($s['nome']) ?>" data-duracao="<?= $s['duracao_minutos'] ?>" data-icone="<?= resolverIconeServico($s['icone'] ?? '', $s['nome']) ?>">
                                    <?= resolverIconeServico($s['icone'] ?? '', $s['nome'] ?? '') ?> <?= htmlspecialchars($s['nome']) ?> — R$ <?= number_format($s['preco'], 2, ',', '.') ?> (<?= $s['duracao_minutos'] ?>m)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="servicoBoundBadge" class="service-bound-badge">
                            <span>💰 <strong>Valor atrelado:</strong> <span id="lblValorAtrelado">R$ 0,00</span></span>
                            <span>⏱️ <strong>Duração estimada:</strong> <span id="lblDuracaoAtrelada">30 min</span></span>
                        </div>
                    </div>

                    <!-- Data e Horário -->
                    <div class="form-group" style="margin-bottom: 1.1rem;">
                        <label>Data e Horário do Atendimento: <span class="required">*</span></label>
                        <input type="datetime-local" name="data_hora" id="agendamentoDataHora" class="form-control" required>
                    </div>

                    <!-- Campo de Observações / Recomendações mantido no PopUp -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Observações / Recomendações:</label>
                        <textarea name="observacoes" id="agendamentoObservacoes" class="form-control" rows="3" placeholder="Ex: Cuidado com os ouvidos, usar shampoo neutro, tosa higiênica, comportamento arisco..."></textarea>
                    </div>

                    <div class="modal-footer" style="padding: 0; border: none;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalAgendamento()">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSalvarAgendamento">💾 Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Container Fixo de Notificações Toast (Canto Inferior Direito) -->
    <div id="toastContainer" class="toast-container-bottom-right"></div>

    <!-- ===============================
         SCRIPTS GLOBAIS COMPARTILHADOS
         =============================== -->
    <script>
        // Sistema de Notificações Toast no Canto Inferior Direito
        function mostrarToast(mensagem, tipo = 'success', icone = '✅', duracao = 5000) {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container-bottom-right';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${tipo}`;
            toast.innerHTML = `
                <div class="toast-icon">${icone}</div>
                <div class="toast-content">${mensagem}</div>
                <button type="button" class="toast-close-btn" onclick="fecharToast(this.parentElement)" title="Fechar notificação">✕</button>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('toast-show');
            });

            const timer = setTimeout(() => {
                fecharToast(toast);
            }, duracao);

            toast._timer = timer;
        }

        function fecharToast(toast) {
            if (!toast) return;
            if (toast._timer) clearTimeout(toast._timer);
            toast.classList.remove('toast-show');
            toast.classList.add('toast-hide');
            setTimeout(() => {
                if (toast && toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        }

        // Funções de Agendamento Compartilhadas
        function atualizarPrecoServico() {
            const select = document.getElementById('agendamentoServicoId');
            if (!select || select.selectedIndex < 0) return;
            const opt = select.options[select.selectedIndex];
            if (opt && opt.dataset.preco) {
                const preco = parseFloat(opt.dataset.preco).toFixed(2);
                const duracao = opt.dataset.duracao || '30';
                document.getElementById('agendamentoValor').value = preco;
                document.getElementById('agendamentoServicoNome').value = opt.dataset.nome || '';

                const lblPreco = document.getElementById('lblValorAtrelado');
                if (lblPreco) lblPreco.textContent = 'R$ ' + preco.replace('.', ',');
                const lblDur = document.getElementById('lblDuracaoAtrelada');
                if (lblDur) lblDur.textContent = duracao + ' min';
            }
        }

        function atualizarObservacoesPetAoSelecionar() {
            const selPet = document.getElementById('agendamentoPetId');
            if (!selPet || selPet.selectedIndex < 0) return;
            const opt = selPet.options[selPet.selectedIndex];
            const obsTextarea = document.getElementById('agendamentoObservacoes');
            // Só puxa automaticamente se estiver em modo de novo agendamento
            if (document.getElementById('agendamentoAction').value === 'cadastrar_agendamento') {
                if (opt && opt.dataset.observacoes !== undefined) {
                    obsTextarea.value = opt.dataset.observacoes || '';
                } else {
                    obsTextarea.value = '';
                }
            }
        }

        function abrirModalNovoAgendamento() {
            document.getElementById('modalAgendamentoTitle').innerHTML = '<span>📅</span> Novo Agendamento de Serviço';
            document.getElementById('agendamentoAction').value = 'cadastrar_agendamento';
            document.getElementById('agendamentoId').value = '';
            
            const selPet = document.getElementById('agendamentoPetId');
            selPet.style.display = 'block';
            selPet.value = '';
            const infoPet = document.getElementById('infoPetEdicao');
            if (infoPet) infoPet.style.display = 'none';

            const selServ = document.getElementById('agendamentoServicoId');
            if (selServ && selServ.options.length > 1) {
                selServ.selectedIndex = 1;
                atualizarPrecoServico();
            }

            document.getElementById('agendamentoStatus').value = 'Agendado';
            document.getElementById('agendamentoObservacoes').value = '';

            const now = new Date();
            now.setDate(now.getDate() + 1);
            now.setHours(10, 0, 0, 0);
            const tzoffset = (new Date()).getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - tzoffset)).toISOString().slice(0, 16);
            document.getElementById('agendamentoDataHora').value = localISOTime;

            document.getElementById('btnSalvarAgendamento').textContent = '💾 Confirmar Agendamento';
            document.getElementById('modalAgendamento').classList.add('active');
        }

        function abrirModalNovoAgendamentoComServico(servicoId, servicoPreco, servicoNome) {
            abrirModalNovoAgendamento();
            const selServ = document.getElementById('agendamentoServicoId');
            if (selServ) {
                selServ.value = servicoId;
                atualizarPrecoServico();
            }
        }

        function fecharModalAgendamento() {
            document.getElementById('modalAgendamento').classList.remove('active');
        }

        function handleAgendamentoBackdropClick(e) {
            if (e.target.id === 'modalAgendamento') {
                fecharModalAgendamento();
            }
        }

        // Fechar modais ao pressionar tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (typeof fecharModalDetalhes === 'function') fecharModalDetalhes();
                if (typeof fecharModalEditarServico === 'function') fecharModalEditarServico();
                fecharModalAgendamento();
            }
        });

        // Disparo automático de Toast caso haja mensagem da sessão / POST
        <?php if (!empty($toast_notificacao)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            mostrarToast("<?= addslashes($toast_notificacao['msg']) ?>", "<?= $toast_notificacao['tipo'] ?>", "<?= $toast_notificacao['icone'] ?>", 5000);
        });
        <?php endif; ?>
    </script>
</body>
</html>
