<?php include VIEWS . '/layout/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Editar Nehemias</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?url=nehemias/actualizar">
                <input type="hidden" name="id" value="<?= htmlspecialchars($registro['Id_Nehemias']) ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombres</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Nombres']) ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Apellidos']) ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Numero de Cedula</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Numero_Cedula']) ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefono</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Telefono']) ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lider</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Lider']) ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lider Nehemias</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($registro['Lider_Nehemias']) ?>" disabled>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subido link de Nehemias</label>
                    <input type="text" name="subido_link" class="form-control" value="<?= htmlspecialchars($registro['Subido_Link'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">En Bogota se le subio</label>
                    <input type="text" name="en_bogota_subio" class="form-control" value="<?= htmlspecialchars($registro['En_Bogota_Subio'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Puesto de votacion</label>
                    <input type="text" name="puesto_votacion" class="form-control" value="<?= htmlspecialchars($registro['Puesto_Votacion'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Mesa de votacion</label>
                    <input type="text" name="mesa_votacion" class="form-control" value="<?= htmlspecialchars($registro['Mesa_Votacion'] ?? '') ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="?url=nehemias/lista" class="btn btn-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
