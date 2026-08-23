import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

import validate_pilot_decision_inputs as validator


APPROVED_FIXTURE = """
- `[x]` Consumer cashback and rewards
- `[ ]` B2B merchant affiliate tracking/reconciliation
- `[ ]` Hybrid model

## 8. Owner sign-off

| Role | Name | Decision | Date | Signature/link |
|---|---|---|---|---|
| Product owner | Approved Test Owner | approve | 2026-01-01 | test-record |
| Release owner | Approved Test Owner | approve | 2026-01-01 | test-record |
| Security owner | Approved Test Owner | approve | 2026-01-01 | test-record |
| Finance/payout owner | Approved Test Owner | approve | 2026-01-01 | test-record |

## Current status
"""


class PilotDecisionValidatorTests(unittest.TestCase):
    def test_repository_template_remains_blocked(self):
        result = validator.validate_text(validator.TEMPLATE.read_text(encoding="utf-8"))

        self.assertEqual("blocked_owner_input_required", result["status"])
        self.assertEqual(40, result["required_fields_remaining"])
        self.assertEqual([], result["selected_pilot_models"])
        self.assertEqual(4, result["owner_signoff_rows_remaining"])
        self.assertEqual("not_approved", result["production_approval"])

    def test_backtick_wrapped_selected_model_is_detected(self):
        self.assertEqual(
            [validator.MODEL_OPTIONS["consumer"]],
            validator.checked_models("- `[x]` Consumer cashback and rewards"),
        )

    def test_owner_rows_are_scoped_to_signoff_section(self):
        text = "| Product owner | Earlier field | not a sign-off | value | value |\n" + APPROVED_FIXTURE
        self.assertEqual(4, len(validator.approval_rows(text)))

    def test_complete_approved_fixture_is_ready(self):
        result = validator.validate_text(APPROVED_FIXTURE)

        self.assertEqual("ready_for_phase_1", result["status"])
        self.assertEqual([], result["blocking_reasons"])
        self.assertEqual(1, len(result["selected_pilot_models"]))
        self.assertEqual(0, result["owner_signoff_rows_remaining"])
        self.assertEqual("not_approved", result["production_approval"])


if __name__ == "__main__":
    unittest.main()
