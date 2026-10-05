            </main> <!-- Fin contenido -->
        </div> <!-- Fin page-content-wrapper -->
    </div> <!-- Fin wrapper -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (opcional para dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom App JS -->
    <script src="<?php echo url('assets/js/app.js'); ?>"></script>
<script src="<?php echo url('assets/js/forms.js'); ?>"></script>
<?php foreach (($page_scripts ?? []) as $page_script): ?>
<script src="<?php echo url($page_script); ?>"></script>
<?php endforeach; ?>
</body>
</html>
