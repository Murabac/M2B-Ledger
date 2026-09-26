"""Generate M2B Ledger — Agent Windows Service install guide PDF."""
from pathlib import Path

from fpdf import FPDF

OUT = Path(__file__).resolve().parents[1] / "docs" / "M2B-Ledger-Agent-Install-Guide.pdf"

# Brand palette
GREEN = (22, 163, 74)       # #16a34a
GREEN_DARK = (20, 83, 45)   # #14532d
GREEN_SOFT = (220, 252, 231)  # #dcfce7
INK = (17, 24, 39)          # #111827
MUTED = (107, 114, 128)     # #6b7280
AMBER = (217, 119, 6)       # #d97706
AMBER_SOFT = (254, 243, 199)
WHITE = (255, 255, 255)
CARD = (247, 250, 248)
BORDER = (229, 231, 235)


class GuidePDF(FPDF):
    def header(self):
        if self.page_no() == 1:
            return
        self.set_fill_color(*GREEN_DARK)
        self.rect(0, 0, 210, 12, "F")
        self.set_xy(14, 3)
        self.set_font("Helvetica", "B", 8)
        self.set_text_color(*WHITE)
        self.cell(0, 6, ascii("M2B Ledger | Agent Windows Service Install Guide"), align="L")
        self.set_y(16)

    def footer(self):
        self.set_y(-14)
        self.set_draw_color(*BORDER)
        self.line(14, self.get_y(), 196, self.get_y())
        self.set_y(-11)
        self.set_font("Helvetica", "", 8)
        self.set_text_color(*MUTED)
        self.cell(0, 8, ascii(f"Confidential setup notes - Page {self.page_no()}/{{nb}}"), align="C")


def ascii(text: str) -> str:
    """Helvetica core fonts are Latin-1; normalize fancy punctuation."""
    return (
        text.replace("\u2014", "-")
        .replace("\u2013", "-")
        .replace("\u2018", "'")
        .replace("\u2019", "'")
        .replace("\u201c", '"')
        .replace("\u201d", '"')
        .replace("\u2026", "...")
        .replace("\u2192", "->")
        .replace("\u00b7", "-")
        .replace("—", "-")
        .replace("–", "-")
        .replace("·", "-")
        .replace("→", "->")
    )


def rgb_fill(pdf: FPDF, rgb):
    pdf.set_fill_color(*rgb)


def rgb_text(pdf: FPDF, rgb):
    pdf.set_text_color(*rgb)


def section_banner(pdf: GuidePDF, number: str, title: str, subtitle: str = ""):
    pdf.ln(4)
    y = pdf.get_y()
    if y > 250:
        pdf.add_page()
        y = pdf.get_y()

    rgb_fill(pdf, GREEN)
    pdf.rect(14, y, 8, 18, "F")
    rgb_fill(pdf, GREEN_SOFT)
    pdf.rect(22, y, 174, 18, "F")

    pdf.set_xy(16, y + 4)
    pdf.set_font("Helvetica", "B", 12)
    rgb_text(pdf, WHITE)
    pdf.cell(6, 10, number, align="C")

    pdf.set_xy(26, y + 2)
    pdf.set_font("Helvetica", "B", 13)
    rgb_text(pdf, GREEN_DARK)
    pdf.cell(0, 7, ascii(title))

    if subtitle:
        pdf.set_xy(26, y + 9)
        pdf.set_font("Helvetica", "", 9)
        rgb_text(pdf, MUTED)
        pdf.cell(0, 6, ascii(subtitle))

    pdf.set_y(y + 22)


def body(pdf: GuidePDF, text: str, size: int = 10):
    pdf.set_font("Helvetica", "", size)
    rgb_text(pdf, INK)
    pdf.set_x(14)
    pdf.multi_cell(182, 5.5, ascii(text))
    pdf.ln(1)


def bullet(pdf: GuidePDF, text: str):
    pdf.set_x(18)
    pdf.set_font("Helvetica", "B", 10)
    rgb_text(pdf, GREEN)
    pdf.cell(6, 5.5, ">")
    pdf.set_font("Helvetica", "", 10)
    rgb_text(pdf, INK)
    pdf.multi_cell(172, 5.5, ascii(text))
    pdf.ln(0.5)


def callout(pdf: GuidePDF, title: str, text: str, soft=GREEN_SOFT, accent=GREEN, ink=GREEN_DARK):
    pdf.ln(2)
    if pdf.get_y() > 245:
        pdf.add_page()
    y = pdf.get_y()
    text = ascii(text)
    title = ascii(title)
    pdf.set_font("Helvetica", "", 9)
    lines = max(2, len(text) // 85 + text.count("\n") + 1)
    h = 10 + lines * 5
    rgb_fill(pdf, soft)
    pdf.rect(14, y, 182, h, "F")
    rgb_fill(pdf, accent)
    pdf.rect(14, y, 3, h, "F")
    pdf.set_xy(20, y + 2)
    pdf.set_font("Helvetica", "B", 9)
    rgb_text(pdf, ink)
    pdf.cell(0, 5, title)
    pdf.set_xy(20, y + 7)
    pdf.set_font("Helvetica", "", 9)
    rgb_text(pdf, INK)
    pdf.multi_cell(170, 4.8, text)
    pdf.set_y(y + h + 2)


def code_block(pdf: GuidePDF, code: str):
    pdf.ln(1)
    if pdf.get_y() > 230:
        pdf.add_page()
    y = pdf.get_y()
    lines = ascii(code).strip("\n").split("\n")
    h = 8 + len(lines) * 5
    rgb_fill(pdf, (15, 23, 42))  # slate-900
    pdf.rect(14, y, 182, h, "F")
    pdf.set_xy(18, y + 3)
    pdf.set_font("Courier", "", 8.5)
    rgb_text(pdf, (167, 243, 208))  # green-200
    for line in lines:
        pdf.set_x(18)
        pdf.cell(174, 5, line)
        pdf.ln(5)
    pdf.set_y(y + h + 3)


def table_row(pdf: GuidePDF, left: str, right: str, header: bool = False):
    y = pdf.get_y()
    if y > 270:
        pdf.add_page()
        y = pdf.get_y()
    h = 8
    left, right = ascii(left), ascii(right)
    if header:
        rgb_fill(pdf, GREEN_DARK)
        pdf.rect(14, y, 60, h, "F")
        pdf.rect(74, y, 122, h, "F")
        rgb_text(pdf, WHITE)
        pdf.set_font("Helvetica", "B", 9)
    else:
        rgb_fill(pdf, CARD)
        pdf.rect(14, y, 60, h, "F")
        rgb_fill(pdf, WHITE)
        pdf.rect(74, y, 122, h, "F")
        pdf.set_draw_color(*BORDER)
        pdf.rect(14, y, 182, h)
        rgb_text(pdf, INK)
        pdf.set_font("Helvetica", "", 9)
    pdf.set_xy(16, y + 2)
    pdf.cell(56, 5, left)
    pdf.set_xy(76, y + 2)
    pdf.cell(118, 5, right)
    pdf.set_y(y + h)


def build():
    pdf = GuidePDF(orientation="P", unit="mm", format="A4")
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=18)
    pdf.add_page()

    # ===== Cover hero =====
    rgb_fill(pdf, GREEN_DARK)
    pdf.rect(0, 0, 210, 78, "F")
    rgb_fill(pdf, GREEN)
    pdf.rect(0, 78, 210, 4, "F")

    pdf.set_xy(14, 22)
    pdf.set_font("Helvetica", "B", 11)
    rgb_text(pdf, (187, 247, 208))
    pdf.cell(0, 6, ascii("M2B LEDGER - qb-balances"))

    pdf.set_xy(14, 34)
    pdf.set_font("Helvetica", "B", 26)
    rgb_text(pdf, WHITE)
    pdf.multi_cell(182, 11, "Agent Windows Service\nInstallation Guide")

    pdf.set_xy(14, 62)
    pdf.set_font("Helvetica", "", 11)
    rgb_text(pdf, (220, 252, 231))
    pdf.cell(0, 6, "Step-by-step setup for each company QuickBooks machine")

    pdf.set_y(92)
    body(
        pdf,
        "This guide installs the M2B sync agent as a Windows Service so it starts with Windows, "
        "reads balances from QuickBooks Desktop (or mock mode), and pushes snapshots to your Laravel API. "
        "The mobile app never talks to QuickBooks — only the agent does.",
    )

    callout(
        pdf,
        "Before you start",
        "You need: Administrator PowerShell, .NET 8 runtime, backend reachable "
        "(e.g. http://127.0.0.1:8000), Filament admin login, and (for live mode) QuickBooks Desktop Enterprise.",
    )

    # Overview boxes
    pdf.ln(2)
    pdf.set_font("Helvetica", "B", 12)
    rgb_text(pdf, GREEN_DARK)
    pdf.set_x(14)
    pdf.cell(0, 8, "What you will do")
    pdf.ln(8)

    steps_overview = [
        ("0", "Start backend API"),
        ("1", "Create agent token in Filament"),
        ("2", "Install Windows Service (Admin)"),
        ("3", "Write Production config + token"),
        ("4", "Authorize QuickBooks (live only)"),
        ("5", "Restart service & verify logs"),
        ("6", "Optional: test mobile app"),
    ]
    for num, label in steps_overview:
        y = pdf.get_y()
        rgb_fill(pdf, GREEN_SOFT)
        pdf.circle(20, y + 3.5, 3.2, style="F")
        pdf.set_xy(17.2, y + 1.2)
        pdf.set_font("Helvetica", "B", 8)
        rgb_text(pdf, GREEN_DARK)
        pdf.cell(6, 5, num, align="C")
        pdf.set_xy(26, y + 1)
        pdf.set_font("Helvetica", "", 10)
        rgb_text(pdf, INK)
        pdf.cell(0, 6, label)
        pdf.ln(7)

    # STEP 0
    pdf.add_page()
    section_banner(pdf, "0", "Start the backend API", "Leave this terminal running")
    body(pdf, "In a normal (non-admin) PowerShell window:")
    code_block(
        pdf,
        'cd "C:\\Users\\lappybooks\\MurabacApps\\M2B Ledger\\backend"\n'
        "php artisan serve --host=127.0.0.1 --port=8000",
    )
    bullet(pdf, "Open http://127.0.0.1:8000/up — you should get a healthy response.")
    bullet(pdf, "Admin panel will be at http://127.0.0.1:8000/admin")

    # STEP 1
    section_banner(pdf, "1", "Create an agent token in Filament", "Shown once — copy it immediately")
    bullet(pdf, "Browse to http://127.0.0.1:8000/admin")
    bullet(pdf, "Login: admin@demo.test  /  Password123!")
    bullet(pdf, "Left nav → Agent tokens → Create / New")
    bullet(pdf, "Name it e.g. Local PC or Company Name")
    bullet(pdf, "After save, COPY the plaintext token once (it is never shown again)")
    bullet(pdf, "Demo Company id is usually 1")
    callout(
        pdf,
        "Security",
        "Never commit the token to git. Put it only in appsettings.Production.json on the machine "
        "(that file is gitignored) or in environment variables Agent__AgentToken.",
        soft=AMBER_SOFT,
        accent=AMBER,
        ink=(146, 64, 14),
    )

    # STEP 2
    section_banner(pdf, "2", "Install the Windows Service", "Must use Administrator PowerShell")
    callout(
        pdf,
        "Common mistake",
        "If you see “#requires Administrator”, you are not elevated. "
        "If you see “module agent could not be loaded”, you are already inside the agent folder — "
        "use .\\scripts\\install-service.ps1 instead of agent\\scripts\\...",
        soft=AMBER_SOFT,
        accent=AMBER,
        ink=(146, 64, 14),
    )
    body(pdf, "1. Start menu → Windows PowerShell → right-click → Run as administrator")
    body(pdf, "2. From the repo root:")
    code_block(
        pdf,
        'cd "C:\\Users\\lappybooks\\MurabacApps\\M2B Ledger"\n'
        ".\\agent\\scripts\\install-service.ps1",
    )
    body(pdf, "Or if your prompt is already inside the agent folder:")
    code_block(pdf, ".\\scripts\\install-service.ps1")
    body(pdf, "If PowerShell blocks scripts:")
    code_block(pdf, "Set-ExecutionPolicy -Scope Process Bypass\n.\\agent\\scripts\\install-service.ps1")

    pdf.set_font("Helvetica", "B", 10)
    rgb_text(pdf, GREEN_DARK)
    pdf.set_x(14)
    pdf.cell(0, 7, "What the script does")
    pdf.ln(7)
    bullet(pdf, "Publishes the agent as win-x86 to C:\\Program Files\\QbBalancesAgent")
    bullet(pdf, "Creates Windows Service QbBalancesAgent (display: QB Balances Sync Agent)")
    bullet(pdf, "Sets StartupType = Automatic")
    bullet(pdf, "Does NOT start the service yet — configure the token first")

    # STEP 3
    pdf.add_page()
    section_banner(pdf, "3", "Write Production config + token", "Do NOT edit the repo appsettings.json for the service")
    body(
        pdf,
        "The service reads config from the install folder. Create Production settings there:",
    )
    code_block(pdf, 'notepad "C:\\Program Files\\QbBalancesAgent\\appsettings.Production.json"')
    body(pdf, "Paste this template and replace PASTE_TOKEN_HERE with your Filament token:")
    code_block(
        pdf,
        "{\n"
        '  "Agent": {\n'
        '    "BackendUrl": "http://127.0.0.1:8000",\n'
        '    "AgentToken": "PASTE_TOKEN_HERE",\n'
        '    "CompanyFilePath": "",\n'
        '    "PollIntervalSeconds": 180,\n'
        '    "MockMode": true,\n'
        '    "CompanyId": 1,\n'
        '    "AppName": "QB Balances Sync Agent",\n'
        '    "CustomerMaxReturned": 100\n'
        "  }\n"
        "}",
    )

    pdf.set_font("Helvetica", "B", 10)
    rgb_text(pdf, GREEN_DARK)
    pdf.set_x(14)
    pdf.cell(0, 8, "Key settings")
    pdf.ln(8)
    table_row(pdf, "Setting", "Meaning", header=True)
    table_row(pdf, "MockMode: true", "Fake data — good first test (no QB needed)")
    table_row(pdf, "MockMode: false", "Live QuickBooks COM read")
    table_row(pdf, "CompanyFilePath: \"\"", "Use the company file currently open in QB")
    table_row(pdf, "CompanyId: 1", "Must match Filament company id")
    table_row(pdf, "BackendUrl", "Use https://… in real production hosting")
    pdf.ln(3)

    callout(
        pdf,
        "Tip",
        "Prove the pipeline with MockMode true first. When Filament Sync logs show success, "
        "flip MockMode to false, open QuickBooks, restart the service, and authorize the app.",
    )

    # STEP 4
    section_banner(pdf, "4", "Authorize QuickBooks (live mode only)", "Skip while MockMode is true")
    bullet(pdf, "Open QuickBooks Desktop Enterprise and open the company file")
    bullet(pdf, "Start or restart the service so QB can prompt")
    code_block(pdf, "Start-Service QbBalancesAgent\n# or\nRestart-Service QbBalancesAgent")
    bullet(pdf, "When prompted for QB Balances Sync Agent, choose always allow / unattended access")
    bullet(pdf, "Prefer a view-only QuickBooks user")
    bullet(pdf, "Confirm: Edit → Preferences → Integrated Applications → Allowed")

    # STEP 5
    pdf.add_page()
    section_banner(pdf, "5", "Restart service & verify", "Silent success is normal — check logs")
    callout(
        pdf,
        "Why Start-Service looks like “nothing happened”",
        "Windows Services do not print to your PowerShell window. "
        "Get-Service and the log file are how you verify.",
        soft=AMBER_SOFT,
        accent=AMBER,
        ink=(146, 64, 14),
    )
    code_block(
        pdf,
        "Restart-Service QbBalancesAgent\n"
        "Get-Service QbBalancesAgent\n"
        'Get-Content "C:\\Windows\\SysWOW64\\logs\\agent-*.log" -Tail 40',
    )
    body(
        pdf,
        "Because the agent is published as win-x86, file logs often land under "
        "C:\\Windows\\SysWOW64\\logs\\ (not Program Files). Look for HTTP 200 / Snapshot accepted.",
    )
    bullet(pdf, "Also check Filament → Sync logs → latest row = success")
    bullet(pdf, "401 Unauthorized = missing/wrong AgentToken — fix Production json and restart")
    bullet(pdf, "Empty AgentToken warning in log = Step 3 not done")

    # STEP 6
    section_banner(pdf, "6", "Optional: test the mobile app", "Refresh only reloads the API snapshot")
    code_block(
        pdf,
        "adb reverse tcp:8000 tcp:8000\n"
        'cd "C:\\Users\\lappybooks\\MurabacApps\\M2B Ledger\\mobile"\n'
        "flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000",
    )
    bullet(pdf, "Login example: owner@demo.test / Password123!")
    bullet(pdf, "Pull to refresh after a successful agent cycle")
    bullet(pdf, "Phone refresh does NOT sync QuickBooks — only the agent does")

    # Reference
    pdf.ln(4)
    pdf.set_font("Helvetica", "B", 12)
    rgb_text(pdf, GREEN_DARK)
    pdf.set_x(14)
    pdf.cell(0, 8, "Useful commands")
    pdf.ln(8)
    table_row(pdf, "Action", "Command", header=True)
    table_row(pdf, "Status", "Get-Service QbBalancesAgent")
    table_row(pdf, "Start", "Start-Service QbBalancesAgent")
    table_row(pdf, "Stop", "Stop-Service QbBalancesAgent")
    table_row(pdf, "Restart", "Restart-Service QbBalancesAgent")
    table_row(pdf, "Uninstall", ".\\agent\\scripts\\uninstall-service.ps1")
    table_row(pdf, "Tail logs", "Get-Content ...\\SysWOW64\\logs\\agent-*.log -Tail 40")

    pdf.ln(6)
    pdf.set_font("Helvetica", "B", 12)
    rgb_text(pdf, GREEN_DARK)
    pdf.set_x(14)
    pdf.cell(0, 8, "Architecture reminder")
    pdf.ln(8)
    body(
        pdf,
        "Company QB PC  →  Agent (this service)  →  HTTPS POST /api/agent/sync  →  Laravel API  →  "
        "one Play Store app. Each company gets its own agent token and users (company_id).",
    )

    rgb_fill(pdf, GREEN_SOFT)
    y = pdf.get_y() + 4
    pdf.rect(14, y, 182, 22, "F")
    pdf.set_xy(20, y + 5)
    pdf.set_font("Helvetica", "B", 10)
    rgb_text(pdf, GREEN_DARK)
    pdf.cell(0, 5, "More docs")
    pdf.set_xy(20, y + 11)
    pdf.set_font("Helvetica", "", 9)
    rgb_text(pdf, INK)
    pdf.cell(0, 5, ascii("README.md - docs/TESTING.md - agent/README.md - docs/ARCHITECTURE.md"))

    OUT.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(OUT))
    print(OUT)


if __name__ == "__main__":
    build()
