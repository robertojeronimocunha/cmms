from app.models.setor import Setor
from app.models.setor_responsavel import SetorResponsavel
from app.models.asset import Asset
from app.models.ativo_categoria import AtivoCategoria
from app.models.attachment import WorkOrderAttachment
from app.models.checklist import ChecklistExecutada, ChecklistPadrao, ChecklistTarefaExecutada, ChecklistTarefaPadrao
from app.models.emulsion import EmulsionInspection
from app.models.lubricant import Lubricant
from app.models.lubrication_execution import LubricationExecution
from app.models.lubrication_point import LubricationPoint
from app.models.maintenance_plan import MaintenancePlan
from app.models.part import Part
from app.models.system_log import SystemLog
from app.models.tag_defeito import TagDefeito
from app.models.user import User
from app.models.work_order_log import WorkOrderLog
from app.models.work_order_part_request import WorkOrderPartRequest
from app.models.work_order import WorkOrder
from app.models.agendador_tarefa import AgendadorTarefa

__all__ = [
    "User",
    "WorkOrder",
    "WorkOrderLog",
    "WorkOrderPartRequest",
    "WorkOrderAttachment",
    "ChecklistPadrao",
    "ChecklistTarefaPadrao",
    "ChecklistExecutada",
    "ChecklistTarefaExecutada",
    "SystemLog",
    "Asset",
    "AtivoCategoria",
    "Setor",
    "SetorResponsavel",
    "Part",
    "MaintenancePlan",
    "Lubricant",
    "LubricationExecution",
    "LubricationPoint",
    "EmulsionInspection",
    "TagDefeito",
    "AgendadorTarefa",
]
