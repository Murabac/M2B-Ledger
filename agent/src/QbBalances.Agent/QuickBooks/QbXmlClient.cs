using System.Xml.Linq;

namespace QbBalances.Agent.QuickBooks;

/// <summary>
/// Sole builder of qbXML request documents. Only QueryRq roots are allowed (read-only).
/// </summary>
public sealed class QbXmlClient
{
    public const string FallbackVersion = "13.0";

    public string BuildRequest(
        string requestRootElementName,
        IEnumerable<XElement>? children = null,
        IEnumerable<XAttribute>? attributes = null,
        string qbXmlVersion = FallbackVersion)
    {
        EnsureQueryRequest(requestRootElementName);

        var request = new XElement(requestRootElementName, children ?? []);
        if (attributes is not null)
        {
            request.Add(attributes);
        }

        var qbxml = new XDocument(
            new XDeclaration("1.0", null, null),
            new XElement(
                "QBXML",
                new XElement(
                    "QBXMLMsgsRq",
                    new XAttribute("onError", "stopOnError"),
                    request)));

        var xml = qbxml.ToString(SaveOptions.DisableFormatting);
        return $"<?xml version=\"1.0\"?><?qbxml version=\"{qbXmlVersion}\"?>{xml}";
    }

    public string BuildAccountQuery(string qbXmlVersion = FallbackVersion)
    {
        return BuildRequest(
            "AccountQueryRq",
            [new XElement("ActiveStatus", "All")],
            qbXmlVersion: qbXmlVersion);
    }

    public string BuildCustomerQuery(int maxReturned, string? iteratorId = null, string qbXmlVersion = FallbackVersion)
    {
        var attributes = new List<XAttribute>();
        if (string.IsNullOrWhiteSpace(iteratorId))
        {
            attributes.Add(new XAttribute("iterator", "Start"));
        }
        else
        {
            attributes.Add(new XAttribute("iterator", "Continue"));
            attributes.Add(new XAttribute("iteratorID", iteratorId));
        }

        return BuildRequest(
            "CustomerQueryRq",
            [
                new XElement("MaxReturned", maxReturned.ToString()),
                new XElement("ActiveStatus", "All"),
            ],
            attributes,
            qbXmlVersion);
    }

    public static void EnsureQueryRequest(string requestRootElementName)
    {
        if (string.IsNullOrWhiteSpace(requestRootElementName))
        {
            throw new ArgumentException("Request root element name is required.", nameof(requestRootElementName));
        }

        if (!requestRootElementName.EndsWith("QueryRq", StringComparison.Ordinal))
        {
            throw new InvalidOperationException(
                $"Read-only agent rejected non-query qbXML root '{requestRootElementName}'. Root must end with 'QueryRq'.");
        }
    }
}
