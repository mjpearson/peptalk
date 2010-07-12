        </div>
        <script type="text/javascript">
            ptc.operator = <?php echo Session::isOperator() ? 'true' : 'false'; ?>;
            ptc.refresh();

            window.onbeforeunload = function() {
                ptc.disconnect(true);
            }
        </script>
    </body>
</html>